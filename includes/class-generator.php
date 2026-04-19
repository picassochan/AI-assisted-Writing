<?php
/**
 * AI article generation logic.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

class AIAW_Generator {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_aiaw_generate', array( $this, 'ajax_generate' ) );
		add_action( 'wp_ajax_aiaw_generate_outline', array( $this, 'ajax_generate_outline' ) );
		add_action( 'wp_ajax_aiaw_expand_section', array( $this, 'ajax_expand_section' ) );
		add_action( 'wp_ajax_aiaw_generate_tags', array( $this, 'ajax_generate_tags' ) );
		add_action( 'wp_ajax_aiaw_create_post', array( $this, 'ajax_create_post' ) );
	}

	private function verify_request() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}
	}

	public function ajax_generate() {
		$this->verify_request();

		$cat_id   = sanitize_text_field( wp_unslash( $_POST['category_id'] ) );
		$topic_id = sanitize_text_field( wp_unslash( $_POST['topic_id'] ) );
		$keywords = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '';

		$template = AIAW_Template::get_instance();
		$cat   = $template->get_category( $cat_id );
		$topic = $template->get_topic( $cat_id, $topic_id );

		if ( ! $cat || ! $topic ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category or topic.', 'ai-assisted-writing' ) ) );
		}

		$prompt   = $this->build_prompt( $cat, $topic, $keywords, 'full' );
		$messages = $this->build_messages( $prompt );

		$result = AIAW_API::get_instance()->generate( $messages );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'content'       => $result,
			'category_id'   => $cat['wp_category_id'],
			'category_name' => $cat['name'],
		) );
	}

	public function ajax_generate_outline() {
		$this->verify_request();

		$cat_id   = sanitize_text_field( wp_unslash( $_POST['category_id'] ) );
		$topic_id = sanitize_text_field( wp_unslash( $_POST['topic_id'] ) );
		$keywords = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '';

		$template = AIAW_Template::get_instance();
		$cat   = $template->get_category( $cat_id );
		$topic = $template->get_topic( $cat_id, $topic_id );

		if ( ! $cat || ! $topic ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category or topic.', 'ai-assisted-writing' ) ) );
		}

		$prompt   = $this->build_prompt( $cat, $topic, $keywords, 'outline' );
		$messages = $this->build_messages( $prompt );

		$result = AIAW_API::get_instance()->generate( $messages );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'outline'       => $result,
			'category_id'   => $cat['wp_category_id'],
			'category_name' => $cat['name'],
		) );
	}

	public function ajax_expand_section() {
		$this->verify_request();

		$outline       = wp_kses_post( wp_unslash( $_POST['outline'] ) );
		$section_index = absint( $_POST['section_index'] );
		$section_title = sanitize_text_field( wp_unslash( $_POST['section_title'] ) );
		$keywords      = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '';

		$prompt = sprintf(
			/* translators: 1: section title 2: outline context 3: extra keywords */
			__( "Write a detailed, engaging section for the heading: \"%1\$s\"\n\nFull outline context:\n%2\$s\n\n%3\$s\n\nWrite in a professional yet accessible tone. Use markdown formatting. The section should be 300-500 words.", 'ai-assisted-writing' ),
			$section_title,
			$outline,
			$keywords ? sprintf( __( 'Focus on these keywords: %s', 'ai-assisted-writing' ), $keywords ) : ''
		);

		$messages = $this->build_messages( $prompt );
		$result   = AIAW_API::get_instance()->generate( $messages );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'content' => $result ) );
	}

	public function ajax_generate_tags() {
		$this->verify_request();

		$content = wp_kses_post( wp_unslash( $_POST['content'] ) );

		$prompt = sprintf(
			/* translators: %s: article content */
			__( 'Based on the following article, suggest 3 to 5 relevant tags as a JSON array of strings. Only return the JSON array, nothing else.\n\nArticle:\n%s', 'ai-assisted-writing' ),
			$content
		);

		$messages = $this->build_messages( $prompt );
		$result   = AIAW_API::get_instance()->generate( $messages );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$tags = json_decode( $result, true );
		if ( ! is_array( $tags ) ) {
			preg_match_all( '/"([^"]+)"/', $result, $matches );
			$tags = isset( $matches[1] ) ? $matches[1] : array();
		}

		wp_send_json_success( array( 'tags' => array_slice( $tags, 0, 5 ) ) );
	}

	public function ajax_create_post() {
		$this->verify_request();

		$title       = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		$content     = wp_kses_post( wp_unslash( $_POST['content'] ) );
		$category_id = absint( $_POST['category_id'] );
		$tags        = isset( $_POST['tags'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tags'] ) ) : array();
		$status      = sanitize_text_field( wp_unslash( $_POST['status'] ) );

		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			$status = 'draft';
		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_type'    => 'post',
		);

		if ( $category_id > 0 ) {
			$post_data['post_category'] = array( $category_id );
		}

		if ( ! empty( $tags ) ) {
			$post_data['tags_input'] = $tags;
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		wp_send_json_success( array(
			'post_id'  => $post_id,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
		) );
	}

	private function build_prompt( $cat, $topic, $keywords, $mode ) {
		$keywords_part = $keywords
			? sprintf( __( "\n\nFocus on these keywords: %s", 'ai-assisted-writing' ), $keywords )
			: '';

		if ( 'outline' === $mode ) {
			return sprintf(
				/* translators: 1: topic name 2: category name 3: custom prompt 4: keywords */
				__( 'Create a detailed article outline for the topic "%1$s" in the category "%2$s".%3$s%4$s\n\nReturn the outline in markdown format with H2 headings and brief bullet points under each. Include an introduction and conclusion section.', 'ai-assisted-writing' ),
				$topic['name'],
				$cat['name'],
				$topic['prompt'] ? "\n\nAdditional instructions: " . $topic['prompt'] : '',
				$keywords_part
			);
		}

		return sprintf(
			/* translators: 1: topic name 2: category name 3: custom prompt 4: keywords */
			__( 'Write a complete, well-structured article about "%1$s" in the category "%2$s".%3$s%4$s\n\nRequirements:\n- Use proper headings (H1 for title, H2 for sections)\n- Include an introduction and conclusion\n- Write in a professional yet engaging tone\n- Aim for 800-1500 words\n- Use markdown formatting', 'ai-assisted-writing' ),
			$topic['name'],
			$cat['name'],
			$topic['prompt'] ? "\n\nAdditional instructions: " . $topic['prompt'] : '',
			$keywords_part
		);
	}

	private function build_messages( $prompt ) {
		return array(
			array(
				'role'    => 'system',
				'content' => __( 'You are a professional content writer for a WordPress blog. Write clear, engaging, and SEO-friendly content. Always respond in the same language as the user\'s request.', 'ai-assisted-writing' ),
			),
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		);
	}
}

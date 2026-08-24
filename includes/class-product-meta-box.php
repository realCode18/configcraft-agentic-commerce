<?php
/**
 * Product editor readiness panel.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Shows current readiness and remediation guidance on product edit screens.
 */
final class Product_Meta_Box {
	/**
	 * WooCommerce product data adapter.
	 *
	 * @var Product_Data_Extractor
	 */
	private $extractor;

	/**
	 * Product readiness scoring service.
	 *
	 * @var Product_Readiness_Evaluator
	 */
	private $evaluator;

	/**
	 * Constructor.
	 *
	 * @param Product_Data_Extractor      $extractor Product adapter.
	 * @param Product_Readiness_Evaluator $evaluator Product scoring service.
	 */
	public function __construct( Product_Data_Extractor $extractor, Product_Readiness_Evaluator $evaluator ) {
		$this->extractor = $extractor;
		$this->evaluator = $evaluator;
	}

	/**
	 * Register product editor hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'add_meta_boxes_product', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Register the product readiness meta box.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'dxaic-product-readiness',
			__( 'AI Commerce Readiness', 'destinx-ai-commerce' ),
			array( $this, 'render' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render the current product evaluation.
	 *
	 * @param \WP_Post $post Product post.
	 * @return void
	 */
	public function render( $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			echo '<p>' . esc_html__( 'Save the product before running its readiness evaluation.', 'destinx-ai-commerce' ) . '</p>';
			return;
		}

		$evaluation = $this->evaluator->evaluate( $this->extractor->extract( $product ) );
		?>
		<div class="dxaic-product-score">
			<strong><?php echo esc_html( $evaluation['score'] ); ?>/100</strong>
			<span class="dxaic-status dxaic-status--<?php echo esc_attr( $evaluation['status'] ); ?>">
				<?php echo esc_html( Issue_Catalog::status_label( $evaluation['status'] ) ); ?>
			</span>
		</div>
		<p class="dxaic-product-pricing dxaic-product-pricing--<?php echo esc_attr( $evaluation['pricing']['mode'] ); ?>">
			<?php echo esc_html( Pricing_Context::display_label( $evaluation['pricing'] ) ); ?>
		</p>
		<p class="dxaic-pricing-verification"><?php echo esc_html( Pricing_Context::verification_label( $evaluation['pricing'] ) ); ?></p>
		<?php if ( empty( $evaluation['issues'] ) ) : ?>
			<p class="dxaic-clear"><?php esc_html_e( 'No catalog findings for this product.', 'destinx-ai-commerce' ); ?></p>
		<?php else : ?>
			<div class="dxaic-remediation-list">
				<?php foreach ( $evaluation['issues'] as $issue ) : ?>
					<details>
						<summary><?php echo esc_html( Issue_Catalog::label( $issue['code'] ) ); ?></summary>
						<p><?php echo esc_html( Issue_Catalog::guidance( $issue['code'] ) ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=destinx-ai-commerce' ) ); ?>"><?php esc_html_e( 'Open full catalog audit', 'destinx-ai-commerce' ); ?></a></p>
		<?php
	}
}

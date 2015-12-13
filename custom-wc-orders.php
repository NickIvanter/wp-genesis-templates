<?php
/*
Template Name: Отчёт по заказам
*/

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( 'This page is private.' );
}

add_action ('genesis_entry_footer', 'show_my_custom_orders');

function show_my_custom_orders() {
	$page_id    = get_the_ID();
	$product_id = (int) get_post_meta( $page_id, 'report_product_id', true );
	$after      = strtotime( get_post_meta( $page_id, 'report_after_date', true ) );
	$paged      = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	if ( $product_id && $after ) {

	global $wpdb;

	$after = strftime( '%Y-%m-%d %H:%M:%S', $after );

	// Получаем id релевантных посты-заказы.
	$sql       = $wpdb->prepare( "SELECT oi.order_id FROM {$wpdb->prefix}woocommerce_order_itemmeta AS om
                               JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON om.order_item_id=oi.order_item_id
                               JOIN {$wpdb->posts} AS p ON p.ID=oi.order_id
                               WHERE om.meta_key='_product_id' AND om.meta_value=%d AND p.post_date >=%s", $product_id, $after );
	$order_ids = $wpdb->get_col( $sql );

	if ( $order_ids ) { // Дальше используем стандартные методы

	$args = array(
		'ignore_sticky_posts' => true,
		'posts_per_page'      => 5,
		'paged'               => $paged,
		'post_type'           => 'shop_order',
		'post_status'         => 'publish',
		'post__in'            => $order_ids, // Только релевантные посты
	);

	$loop = new WP_Query( $args );

	while ( $loop->have_posts() ) {
		$loop->the_post();
		$order_id = $loop->post->ID;
		$order    = new WC_Order( $order_id );
		?>
		<article>
			<header>
				<h2><?php _e( 'Order', 'woocommerce' ); ?>
					№<?php echo $order_id; ?> &mdash; <?php the_time( 'd.m.Y h:i:s' ); ?></time></h2>
			</header>
			<table cellspacing="0" cellpadding="2">
				<thead>
				<tr>
					<th scope="col" style="text-align:left;"><?php _e( 'Product', 'woocommerce' ); ?></th>
					<th scope="col" style="text-align:left;"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
					<th scope="col" style="text-align:left;"><?php _e( 'Price', 'woocommerce' ); ?></th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<th scope="row" colspan="2"
					    style="text-align:left; padding-top: 12px;"><?php _e( 'Subtotal:', 'woocommerce' ); ?></th>
					<td style="text-align:left; padding-top: 12px;"><?php echo $order->get_subtotal_to_display(); ?></td>
				</tr>
				<tr>
					<th scope="row" colspan="2"
					    style="text-align:left; padding-top: 12px;"><?php _e( 'Status', 'woocommerce' ); ?></th>
					<td style="text-align:left; padding-top: 12px;"><?php echo _e( wc_get_order_status_name( get_post_status() ), 'woocommerce' ); ?></td>
				</tr>
				<?php if ( $order->order_shipping > 0 ) : ?>
					<tr>
						<th scope="row" colspan="2"
						    style="text-align:left;"><?php _e( 'Shipping:', 'woocommerce' ); ?></th>
						<td style="text-align:left;"><?php echo $order->get_shipping_to_display(); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $order->order_discount > 0 ) : ?>
					<tr>
						<th scope="row" colspan="2"
						    style="text-align:left;"><?php _e( 'Discount:', 'woocommerce' ); ?></th>
						<td style="text-align:left;"><?php echo woocommerce_price( $order->order_discount ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $order->get_total_tax() > 0 ) : ?>
					<tr>
						<th scope="row" colspan="2" style="text-align:left;"><?php _e( 'Tax:', 'woocommerce' ); ?></th>
						<td style="text-align:left;"><?php echo woocommerce_price( $order->get_total_tax() ); ?></td>
					</tr>
				<?php endif; ?>
				<tr>
					<th scope="row" colspan="2" style="text-align:left;"><?php _e( 'Total:', 'woocommerce' ); ?></th>
					<td style="text-align:left;"><?php echo woocommerce_price( $order->order_total ); ?></td>
				</tr>
				</tfoot>
				<tbody>
				<?php echo $order->email_order_items_table(); ?>
				</tbody>
			</table>

			<h2><?php _e( 'Customer details', 'woocommerce' ); ?></h2>

			<p>
				<?php if ( $order->billing_first_name ) : ?>
					<strong><?php _e( 'First name', 'woocommerce' ); ?>
						:</strong> <?php echo $order->billing_first_name; ?>
				<?php endif; ?>
				<?php if ( $order->billing_city ) : ?>
					<strong><?php _e( 'City', 'woocommerce' ); ?>:</strong> <?php echo $order->billing_city; ?>
				<?php endif; ?>
			</p>

			<div style="clear:both;"></div>
		</article>
	<?php } ?>
	<nav>
		<span style="float:left"><?php previous_posts_link( '&laquo; Назад', $loop->max_num_pages ) ?></span>
		<span style="float:right"><?php next_posts_link( 'Вперёд &raquo;', $loop->max_num_pages ) ?></span>
	</nav>
</section>
<?php
wp_reset_query();
} else { ?>
	<section>
		<p>Заказов товара <?php echo $product_id ?> после <? echo $after ?> нет.</p>
	</section>
	<?php
}
}
}
genesis();

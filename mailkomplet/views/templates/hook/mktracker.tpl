<script type="text/javascript">var a="{$mk_identity}",spSrc=("https:"==document.location.protocol?"https":"http")+"://tracker.mail-komplet.cz/tracker.js?instance="+encodeURI(a);document.write(unescape("%3Cscript src='"+spSrc+"' type='text/javascript'%3E%3C/script%3E"));</script>

{if !empty($cart_items) || $customer_email || $order_id}
<script type="text/javascript">
{if !empty($cart_items)}
MkTracker.updateBasketContent([
{foreach $cart_items as $cart_item}
	{
		"amount": {$cart_item['cart_quantity']},
		"name": "{$cart_item['name']}",
		"url": "{$cart_item['product_url']}",
		"productImageUrl": "{$cart_item['image_url']}",
		"code": {$cart_item['id_product']},
		"currency": {$currency_id},
		"price": {$cart_item['price']},
		"priceVat": {$cart_item['price_wt']},
		"category": "{$cart_item['category']}",
		"manufacturer": "{$cart_item['manufacturer']}"
	},
{/foreach}
]);
{/if}

{if $customer_email}
MkTracker.identifyVisitor("{$customer_email}");
{/if}

{if $order_id}
MkTracker.sessionCompleted({$order_id})
{/if}
</script>
{/if}
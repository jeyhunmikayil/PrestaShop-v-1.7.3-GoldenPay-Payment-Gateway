{extends "$layout"}
{block name="content"}
<section>
  <h2>{l s="Please, wait...You redirect to payment page"}</h2>
  <form action="/payment/pay.php" method="POST" id="goldenpay">
    {foreach from=$params key=name item=value}
    <input type="hidden" name="{$name}" value="{$value}">
    {/foreach}
  </form>
  <script type="text/javascript">document.getElementById("goldenpay").submit();</script>
</section>
{/block}


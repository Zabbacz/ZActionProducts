<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

vmJsApi::jPrice();

$app = Factory::getApplication();
$document = $app->getDocument();
$wa = $document->getWebAssetManager();

$wa->getRegistry()->addExtensionRegistryFile('mod_virtuemart_zactionproducts');
$wa->useStyle('mod_virtuemart_zactionproducts.style');

$language = $app->getLanguage();
$language->load('mod_virtuemart_zactionproducts', JPATH_BASE . '/modules/mod_virtuemart_zactionproducts');
?>

<?php if (!empty($docs)): ?>
<div class="vm-product-grid container my-5">
    <div class="row gy-4 g-xxl-5">

        <?php foreach ($docs as $doc):

            $product_id   = (int)($doc['virtuemart_product_id'] ?? 0);
            if (!$product_id) continue;

            $product_name = $doc['product_name'] ?? '';
            $category_id  = (int)($doc['virtuemart_category_id'] ?? 0);
            $file_url     = $doc['file_url'] ?? '';
            $price        = $doc['product_price'] ?? '';
	    $sDph	  = $doc['s_dph'] ?? '';
            $availability = $doc['product_availability'] ?? '';
            $min_qty      = (int)($doc['min_order_level'] ?? 1);
            $step_qty     = (int)($doc['step_order_level'] ?? 1);

            $product_link = Route::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product_id . '&virtuemart_category_id=' . $category_id);
            $image_src    = Uri::root() . 'images/virtuemart/product/' . ($file_url);
        ?>

        <div class="product col-6 col-md-6 col-lg-3 row-1 w-desc-1">
            <div class="product-container d-flex flex-column h-100" data-vm="product-container">

                <div class="vm-product-media-container text-center d-flex flex-column justify-content-center" style="min-height:300px">

                    <form method="post" class="product js-recalculate"
                          action="<?php echo Route::_('index.php?option=com_virtuemart&view=cart'); ?>">

                        <div class="main-image">
                            <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="<?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?>"
                                 loading="lazy"
                                 class="img-fluid" />
                        </div>

                        <div class="nazev-produktu mt-2">
                            <a href="<?php echo htmlspecialchars($product_link, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>

                        <strong class="d-block mt-2">
                            <?php echo htmlspecialchars(Text::_('MOD_VIRTUEMART_ZSEARCHSPHINX_AVAILABILITY') . ' ' . $availability, ENT_QUOTES, 'UTF-8'); ?>
                        </strong>
			<?php
			    $obchod = $params->get('obchod', '2', 'STRING');  //velkoobchod = 1, maloobchod = 2
			    if ($obchod === '1'){
			    ?>
                        <div class="cena mt-2 mb-2">
                            <i><?php echo htmlspecialchars(Text::_('MOD_VIRTUEMART_ZSEARCHSPHINX_PRICE') . ' ' . $price . ' Kč bez DPH/ks', ENT_QUOTES, 'UTF-8'); ?></i>
                        </div>
			    <?php } else {?>
                        <div class="cena mt-2 mb-2">
                            <i><?php echo htmlspecialchars(Text::_('MOD_VIRTUEMART_ZSEARCHSPHINX_PRICE') . ' ' . round($sDph, 0). ' Kč /ks', ENT_QUOTES, 'UTF-8'); ?></i>
                        </div>
			    <?php }?>
			<?php
		            $velkoobchod = $params->get('velkoobchod_id','','STRING');
			    if(($obchod ==='1' AND (Factory::getUser()->groups[(int)$velkoobchod]===(int)$velkoobchod)) OR $obchod ==='2') {
			?>
			    <input class="quantity-input"
				type="number"
				name="quantity[]"
				value="<?php echo $min_qty; ?>"
				min="<?php echo $min_qty; ?>"
				step="<?php echo $step_qty; ?>" />

			    <input type="submit"
				name="addtocart"
				class="btn btn-primary addtocart-button mt-3"
				value="<?php echo Text::_('COM_VIRTUEMART_CART_ADD_TO'); ?>" />

			    <input type="hidden" name="virtuemart_product_id[]" value="<?php echo $product_id; ?>" />
			    <input type="hidden" name="option" value="com_virtuemart" />
			    <input type="hidden" name="view" value="cart" />
			    <noscript><input type="hidden" name="task" value="add"/></noscript>  
			<?php					
			    }					    
			else{
			    echo 'Nakupovat mohou pouze registrovaní obchodníci.';
			}
			?>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </form>

                </div>

            </div>
        </div>

        <?php endforeach; ?>

    </div>
</div>
<?php endif;

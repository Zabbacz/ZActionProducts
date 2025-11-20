<?php

namespace Zabba\Module\ZActionProducts\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    
    protected function getLayoutData(): array
    {
	$params = new Registry($this->module->params);
	$data = parent::getLayoutData();
        $data['docs'] = $this->getHelperFactory()->getHelper('ZActionProductsHelper')->getProducts($params);
    return $data;

    }
}
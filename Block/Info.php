<?php
/*
 * Filename: /project/magento234/magento/app/code/Ace/Core/Block/Info.php
 * Path: /project/magento234/magento/app/code/Ace/Core/Block
 * Created Date: Sunday, April 5th 2020, 12:59:32 pm
 * Author: durga
 * 
 * Copyright (c) 2020 Your Company
 * @todo Show Ace extension detail
 */

namespace Ace\Core\Block;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Json\DecoderInterface;

class Info extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $_layoutFactory;
    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $_moduleReader;
    /**
     * @var DecoderInterface
     */
    protected $_jsonDecoder;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_filesystem;


    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Filesystem\Driver\File $filesystem,
        DecoderInterface $jsonDecoder,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);

        $this->_moduleList    = $moduleList;
        $this->_layoutFactory = $layoutFactory;
        $this->_moduleReader  = $moduleReader;
        $this->_jsonDecoder   = $jsonDecoder;
        $this->_filesystem    = $filesystem;
        $this->_scopeConfig   = $context->getScopeConfig();
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $modules = $this->_moduleList->getNames();

        $dispatchResult = new \Magento\Framework\DataObject($modules);
        $modules = $dispatchResult->toArray();

        sort($modules);
        foreach ($modules as $moduleName) {
            if (strstr($moduleName, 'Ace_') === false
                || $moduleName === 'Ace_Core'
            ) {
                continue;
            }

            $html .= $this->_getFieldHtml($element, $moduleName);
        }

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $layout = $this->_layoutFactory->create();

            $this->_fieldRenderer = $layout->createBlock(
                'Magento\Config\Block\System\Config\Form\Field'
            );
        }

        return $this->_fieldRenderer;
    }

    /**
     * Read info about extension from composer json file
     * @param $moduleCode
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function _getModuleInfo($moduleCode)
    {
        $dir = $this->_moduleReader->getModuleDir('', $moduleCode);
        $file = $dir . '/composer.json';

        $string = $this->_filesystem->fileGetContents($file);
        $json = $this->_jsonDecoder->decode($string);

        return $json;
    }

    /**
     * @param $fieldset
     * @param $moduleCode
     * @return string
     */
    protected function _getFieldHtml($fieldset, $moduleCode)
    {
        $module = $this->_getModuleInfo($moduleCode);
        if(!is_array($module)  ||
           !array_key_exists('version', $module) ||
           !array_key_exists('description', $module)
        ) {
            return '';
        }

        $currentVer = $module['version'];
        $moduleName = $module['description'];
        $moduleName = $this->_replaceAceText($moduleName);
        $status =
             '<a target="_blank">
                <img src="'. $this->getViewFileUrl('Ace_Core::images/ok.gif') . '" title="' . __("Installed") . '"/>
             </a>';

       

        // in case if module output disabled
        if ($this->_scopeConfig->getValue('advanced/modules_disable_output/' . $moduleCode)) {
            $status =
                '<a href="' . $url . '" target="_blank">
                        <img src="' . $this->getViewFileUrl('Ace_Core::images/bad.gif') .
                            '" alt="' . __("Output disabled") . '" title="'. __("Output disabled")
                .'"/></a>';
        }

        $moduleName = $status . ' ' . $moduleName;

        $field = $fieldset->addField($moduleCode, 'label', array(
            'name'  => 'dummy',
            'label' => $moduleName,
            'value' => $currentVer,
        ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * @param $moduleName
     * @return mixed
     */
    protected function _replaceAceText($moduleName)
    {
        $moduleName = str_replace('for Magento 2', '', $moduleName);
        $moduleName = str_replace('by Ace', '', $moduleName);

        return $moduleName;
    }
}
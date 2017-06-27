<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * Copyright (c) 2017. All rights reserved Team Ever
 *
 * This program is free software
 * It is not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the module canvas happens at your own risk.
 *
 * @author      Team Ever <contact@team-ever.com>
 * @copyright   Team Ever (https://www.team-ever.com)
 * @license     http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class EverPsCanvas extends Module implements WidgetInterface
{
    private $templateFile;
    private $_postErrors = array();
    private $_html = '';

    public function __construct()
    {
        $this->name = 'everpscanvas';
        $this->tab = 'front_office_features';
        $this->version = '0.1';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ever Ps Canvas');
        $this->description = $this->l('A Prestashop 1.7 Module Canvas.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module ?');

        $this->templateFile = 'module:everpscanvas/views/templates/hook/everpscanvas.tpl';
    }



    public function install()
    {
        return parent::install()
            && $this->registerHook('displayTop')
            && $this->registerHook('displayHome')
            && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('EVERPSCANVAS_HTML');
    }


    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            foreach ($this->context->controller->getLanguages() as $language) {
                if (!Tools::getValue('EVERPSCANVAS_HTML_'.$language['id_lang'])) {
                    $this->_postErrors[] = $this->l('The "Content" field is required for all languages.');
                }
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            // get multilanguages field
            $everpscanvas_html = [];
            foreach ($this->context->controller->getLanguages() as $language) {
                $everpscanvas_html[$language['id_lang']] = Tools::getValue('EVERPSCANVAS_HTML_'.$language['id_lang']);
            }
            
            Configuration::updateValue('EVERPSCANVAS_HTML', $everpscanvas_html, true);
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->renderForm();

        return $this->_html;
    }


    public function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form = array(
            'form' => array(
                'tinymce' => true,
                'legend' => array(
                    'title' => $this->l('Ever Ps Canvas Configuration'),
                    'icon' => 'icon-edit'
                ),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Content'),
                        'desc' => $this->l('Content for the home block'),
                        'name' => 'EVERPSCANVAS_HTML',
                        'required' => true,
                        'autoload_rte' => true,
                        'lang' => true
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );
        }

        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        $this->fields_form = array();

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $everpscanvas_html = [];
        foreach ($this->context->controller->getLanguages() as $language) {
            $everpscanvas_html[$language['id_lang']] = Tools::getValue('EVERPSCANVAS_HTML_'.$language['id_lang']);
        }

        return [
            'EVERPSCANVAS_HTML' => ($everpscanvas_html) ? $everpscanvas_html : Configuration::getInt('EVERPSCANVAS_HTML')
        ];
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->registerStylesheet('modules-everpscanvas', 'modules/'.$this->name.'/views/css/everpscanvas.css', ['media' => 'all', 'priority' => 150]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('everpscanvas'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('everpscanvas'));
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return array(
            'content_html' => Configuration::get('EVERPSCANVAS_HTML', $this->context->language->id)
        );
    }
}

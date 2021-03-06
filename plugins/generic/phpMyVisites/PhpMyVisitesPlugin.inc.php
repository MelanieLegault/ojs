<?php

/**
 * @file plugins/generic/phpMyVisites/PhpMyVisitesPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PhpMyVisitesPlugin
 * @ingroup plugins_generic_phpMyVisites
 *
 * @brief phpMyVisites plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class PhpMyVisitesPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			// Insert phpmv page tag to common footer
			HookRegistry::register('Templates::Common::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to article footer
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to article interstitial footer
			HookRegistry::register('Templates::Article::Interstitial::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to article pdf interstitial footer
			HookRegistry::register('Templates::Article::PdfInterstitial::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to reading tools footer
			HookRegistry::register('Templates::Rt::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert phpmv page tag to help footer
			HookRegistry::register('Templates::Help::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.phpmv.displayName');
	}

	function getDescription() {
		return __('plugins.generic.phpmv.description');
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.phpmv.manager.settings'));
		}
		return $verbs;
	}

	/**
	 * Insert phpmv page tag to footer
	 */
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];
			$output =& $params[2];
			$request =& $this->getRequest();
			$templateMgr =& TemplateManager::getManager($request);
			$currentJournal = $templateMgr->get_template_vars('currentJournal');

			if (!empty($currentJournal)) {
				$journal =& $request->getJournal();
				$journalId = $journal->getId();
				$phpmvSiteId = $this->getSetting($journalId, 'phpmvSiteId');
				$phpmvUrl = $this->getSetting($journalId, 'phpmvUrl');

				if (!empty($phpmvSiteId) && !empty($phpmvUrl)) {
					$templateMgr->assign('phpmvSiteId', $phpmvSiteId);
					$templateMgr->assign('phpmvUrl', $phpmvUrl);
					$output .= $templateMgr->fetch($this->getTemplatePath() . 'pageTag.tpl');
				}
			}
		}
		return false;
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request =& $this->getRequest();
		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$journal =& $request->getJournal();

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$this->import('PhpMyVisitesSettingsForm');
				$form = new PhpMyVisitesSettingsForm($this, $journal->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}
}
?>

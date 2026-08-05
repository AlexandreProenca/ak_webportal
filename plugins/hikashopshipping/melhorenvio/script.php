<?php

declare(strict_types=1);

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Table\Table;

return new class () implements InstallerScriptInterface {
	private string $minimumJoomla = '5.0.0';
	private string $minimumPhp = '8.1.0';

	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
			Factory::getApplication()->enqueueMessage(
				'Melhor Envio requer PHP ' . $this->minimumPhp . ' ou superior.',
				'error'
			);
			return false;
		}

		if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
			Factory::getApplication()->enqueueMessage(
				'Melhor Envio requer Joomla ' . $this->minimumJoomla . ' ou superior.',
				'error'
			);
			return false;
		}

		return true;
	}

	public function install(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function update(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		if ($type === 'uninstall') {
			return true;
		}

		$table = Table::getInstance('Extension');
		$extensionId = (int) $table->find(array(
			'type' => 'plugin',
			'folder' => 'hikashopshipping',
			'element' => 'melhorenvio',
		));

		if ($extensionId < 1 || !$table->load($extensionId)) {
			Factory::getApplication()->enqueueMessage(
				'Plugin Melhor Envio instalado, mas não foi possível localizá-lo para ativação.',
				'warning'
			);
			return true;
		}

		$table->enabled = 1;

		if (!$table->store()) {
			Factory::getApplication()->enqueueMessage(
				'Plugin Melhor Envio instalado, mas a ativação automática falhou.',
				'warning'
			);
		}

		return true;
	}
};

<?php
/**
 * DOC Text
 * 
 * @copyright Copyright 2013 Regents of the University of Minnesota
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The DOC Text plugin.
 * 
 * @package Omeka\Plugins\DocText
 */
class DocTextPlugin extends Omeka_Plugin_AbstractPlugin
{
    const ELEMENT_SET_NAME = 'DOC Text';
    const ELEMENT_NAME = 'Text';

    protected $_hooks = array(
        'install',
        'uninstall',
        'initialize',
        'config_form',
        'config',
        'before_save_file',
    );

    protected $_docMimeTypes = array(
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    );

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        // Don't install if the catdoc command doesn't exist.
        // See: http://stackoverflow.com/questions/592620/check-if-a-program-exists-from-a-bash-script
        if ((int) shell_exec('hash catdoc 2>&- || echo 1')) {
            throw new Omeka_Plugin_Installer_Exception(__('The catdoc command-line utility ' 
            . 'is not installed. catdoc must be installed to install this plugin.'));
        }
        // Don't install if a DOC element set already exists.
        if ($this->_db->getTable('ElementSet')->findByName(self::ELEMENT_SET_NAME)) {
            throw new Omeka_Plugin_Installer_Exception(__('An element set by the name "%s" already ' 
            . 'exists. You must delete that element set to install this plugin.', self::ELEMENT_SET_NAME));
        }
        insert_element_set(
            array('name' => self::ELEMENT_SET_NAME, 'record_type' => 'File'),
            array(array('name' => self::ELEMENT_NAME))
        );
    }

    /**
     * Uninstall the plugin
     */
    public function hookUninstall()
    {
        // Delete the DOC element set.
        $this->_db->getTable('ElementSet')->findByName(self::ELEMENT_SET_NAME)->delete();
    }

    /**
     * Initialize this plugin.
     */
    public function hookInitialize()
    {
        // Add translation.
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    /**
     * Display the config form.
     */
    public function hookConfigForm()
    {
        echo get_view()->partial(
            'plugins/doc-text-config-form.php', 
            array('valid_storage_adapter' => $this->isValidStorageAdapter())
        );
    }

    /**
     * Handle the config form.
     */
    public function hookConfig()
    {
        // Run the text extraction process if directed to do so.
        if ($_POST['doc_text_process'] && $this->isValidStorageAdapter()) {
            Zend_Registry::get('bootstrap')->getResource('jobs')
                ->sendLongRunning('DocTextProcess');
        }
    }

    /**
     * Add the DOC text to the file record.
     * 
     * This has a secondary effect of including the text in the search index.
     */
    public function hookBeforeSaveFile($args)
    {
        // Extract text only on file insert.
        if (!$args['insert']) {
            return;
        }
        $file = $args['record'];
        // Ignore non-DOC files.
        if (!in_array($file->mime_type, $this->_docMimeTypes)) {
            return;
        }
        // Add the DOC text to the file record.
        $element = $file->getElement(self::ELEMENT_SET_NAME, self::ELEMENT_NAME);
        $text = $this->docToText($file->getPath());
        // catdoc must return a string to be saved to the element_texts table.
        if (is_string($text)) {
            $file->addTextForElement($element, $text);
        }
    }

    /**
     * Extract the text from a DOC file.
     * 
     * @param string $path
     * @return string
     */
    public function docToText($path)
    {
        $path = escapeshellarg($path);
        return shell_exec("catdoc $path -");
    }

    /**
     * Determine if the plugin supports the storage adapter.
     * 
     * catdoc cannot be used on remote files, so only support the default 
     * Filesystem adapter, which stores files locally.
     * 
     * @return bool
     */
    public function isValidStorageAdapter()
    {
        $storageAdapter = Zend_Registry::get('bootstrap')
            ->getResource('storage')->getAdapter();
        if (!($storageAdapter instanceof Omeka_Storage_Adapter_Filesystem)) {
            return false;
        }
        return true;
    }

    /**
     * Get the DOC MIME types.
     * 
     * @return array
     */
    public function getDocMimeTypes()
    {
        return $this->_docMimeTypes;
    }
}

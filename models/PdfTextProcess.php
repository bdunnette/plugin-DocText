<?php
/**
 * DOC Text
 * 
 * @copyright Copyright 2013 Regents of the University of Minnesota
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Omeka\Plugins\DocText
 */
class DocTextProcess extends Omeka_Job_AbstractJob
{
    /**
     * Process all DOC files in Omeka.
     */
    public function perform()
    {
        $docTextPlugin = new DocTextPlugin;
        $fileTable = $this->_db->getTable('File');

        $select = $this->_db->select()
            ->from($this->_db->File)
            ->where('mime_type IN (?)', $docTextPlugin->getDocMimeTypes());

        // Iterate all DOC file records.
        $pageNumber = 1;
        while ($files = $fileTable->fetchObjects($select->limitPage($pageNumber, 50))) {
            foreach ($files as $file) {

                // Delete any existing DOC text element texts from the file.
                $textElement = $file->getElement(
                    DocTextPlugin::ELEMENT_SET_NAME,
                    DocTextPlugin::ELEMENT_NAME
                );
                $file->deleteElementTextsByElementId(array($textElement->id));

                // Extract the DOC text and add it to the file.
                $file->addTextForElement(
                    $textElement,
                    $docTextPlugin->docToText(FILES_DIR . '/original/' . $file->filename)
                );
                $file->save();

                // Prevent memory leaks.
                release_object($file);
            }
            $pageNumber++;
        }
    }
}

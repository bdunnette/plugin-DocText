<div class="field">
    <div id="doc_text_process_label" class="two columns alpha">
        <label for="doc_text_process"><?php echo __('Process existing doc files'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
        <?php 
        echo __(
            'This plugin enables searching on doc files by extracting '
            . 'their texts and saving them to their file records. This '
            . 'normally happens automatically, but there are times when '
            . 'you\'ll want to extract text from all doc files that '
            . 'already exist in your Omeka repository, like when first '
            . 'installing this plugin. Check the box below and submit '
            . 'this form to run the text extraction process, which may '
            . 'take some time to finish.');
        ?>
        </p>
        <?php if ($this->valid_storage_adapter): ?>
        <?php echo $this->formCheckbox('doc_text_process'); ?>
        <?php else: ?>
        <p class="error">
        <?php
        echo __(
            'This plugin does not support processing of doc files that '
            . 'are stored remotely. Processing existing doc files has been '
            . 'disabled.'
        );
        ?>
        </p>
        <?php endif; ?>
    </div>
</div>

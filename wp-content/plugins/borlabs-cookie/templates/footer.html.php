        <div class="footer text-center mt-4">
            <p>
                developed by
                <br>
                <a href="<?php _ex('https://borlabs.io/?utm_source=Borlabs+Cookie&amp;utm_medium=Footer+Logo&amp;utm_campaign=Analysis', 'Backend / Global / Footer / URL', 'borlabs-cookie'); ?>" rel="nofollow noopener noreferrer" target="_blank"><img class="borlabs-logo" src="<?php echo $this->imagePath; ?>/borlabs-logo.svg" alt="Borlabs"></a>
            </p>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="borlabsModalDelete" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content shadow">
                <div class="modal-header bg-danger text-light">
                    <h5 class="modal-title"><?php _ex('Delete selection?', 'Backend / Global / Headline', 'borlabs-cookie'); ?></h5>
                    <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?php _ex('Close', 'Backend / Global / Button Title', 'borlabs-cookie'); ?></button>
                    <a href="#" class="btn btn-primary btn-danger btn-sm" data-confirm><?php _ex('Delete', 'Backend / Global / Text', 'borlabs-cookie'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div><!-- BorlabsCookie -->
(function($) {
    'use strict';

    $(document).ready(function() {
        $('.lattice-mail-delete-campaign').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this campaign?')) {
                e.preventDefault();
            }
        });

        $('.lattice-mail-delete-subscriber').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this subscriber?')) {
                e.preventDefault();
            }
        });

        $('#mailer-smtp').on('change', function() {
            $('#smtp-settings').toggle($(this).val() === 'smtp');
        });

        if ($('#mailer-smtp').val() !== 'smtp') {
            $('#smtp-settings').hide();
        }
    });

})(jQuery);

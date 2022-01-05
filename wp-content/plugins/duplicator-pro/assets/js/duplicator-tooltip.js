/*! dup tooltip */
(function ($) {
    DuplicatorTooltip = {
        initialized: false,
        messages: {
            'copy': 'Copy to clipboard',
            'copied': 'copied to clipboard',
            'copyUnable': 'Unable to copy'
        },
        load: function () {
            if (this.initialized) {
                return;
            }

            tippy('[data-tooltip]', {
                content: function (ref) {
                    var header = ref.dataset.tooltipTitle;
                    var body = ref.dataset.tooltip;
                    var res = header !== undefined ? '<h3>' + header + '</h3>' : '';

                    res += '<div class="dup-tippy-content">' + body + '</div>';
                    return res;
                },
                allowHTML: true,
                interactive: true,
                placement: 'bottom-start',
                theme: 'duplicator',
                zIndex: 900000,
                appendTo: document.body
            });

            $('[data-dup-copy-value]').each(function () {

                var element = $(this);
                if (element.hasClass('disabled')) {
                    return;
                }

                var tippyElement = tippy(this, {
                    allowHTML: true,
                    placement: 'bottom-start',
                    theme: 'duplicator',
                    zIndex: 900000,
                    hideOnClick: false,
                    trigger: 'manual'
                });

                var copyTitle = element.is('[data-dup-copy-title]') ? element.data('dup-copy-title') : DuplicatorTooltip.messages.copy;
                tippyElement.setContent('<div class="dup-tippy-content">' + copyTitle + '</div>');

                //Have to set manually otherwise might hide on click.
                element.mouseover(function () {
                    tippyElement.show();
                }).mouseout(function () {
                    tippyElement.hide();
                });

                element.click(function () {
                    var valueToCopy = element.data('dup-copy-value');
                    var copiedTitle = element.is('[data-dup-copied-title]') ? element.data('dup-copied-title') : valueToCopy + ' ' + DuplicatorTooltip.messages.copied;
                    var message = DuplicatorTooltip.messages.copyUnable;
                    var tmpArea = jQuery("<textarea></textarea>").css({
                        position: 'absolute',
                        top: '-10000px'
                    }).text(valueToCopy).appendTo("body");
                    tmpArea.select();

                    try {
                        message = document.execCommand('copy') ? copiedTitle : 'Unable to copy';
                    } catch (err) {
                        console.log(err);
                    }

                    tippyElement.setContent('<div class="dup-tippy-content">' + message + '</div>');
                    tippyElement.setProps({ theme: 'duplicator-filled' });

                    setTimeout(function () {
                        tippyElement.setContent('<div class="dup-tippy-content">' + copyTitle + '</div>');
                        tippyElement.setProps({ theme: 'duplicator' });
                    }, 2000);
                });
            });

            this.initialized = true;
        },
        unload: function () {
            var tooltips = document.querySelectorAll('[data-tooltip], [data-dup-copy-value]');
            tooltips.forEach(function (element) {
                if (element._tippy) {
                    element._tippy.destroy();
                }
            });
            this.initialized = false;
        },
        reload: function () {
            this.unload();
            this.load();
        }
    }
})(jQuery);
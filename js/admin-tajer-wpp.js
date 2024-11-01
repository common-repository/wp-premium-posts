jQuery(document).ready(function ($) {
    var WPP = {
        init: function () {
            this.showHidePricingOptions();
            $("#enable_wpp").on('click', this.showHidePricingOptions);
            var priceOptionsDialog = $("#price-options-dialog");
            priceOptionsDialog.on("tajerPriceOptionsDialog", this.changePriceOptions);
            priceOptionsDialog.on("tajerSavePriceOptionsDialog", this.savePriceOptions);
        },
        savePriceOptions: function (e, priceOptionsDialog) {
            var posts_limit = $("#wpp_posts_limit").val(),
                wpp_enable = $("#enable_wpp").val(),
                forPriceOption = $("input:hidden[name='tajer_price_options_for']").val(),
                container = $("table#multiple_price_table").find('tr.tajer_repeatable_row[data-index="' + forPriceOption + '"]');

            container.find("input[name*='posts_limit']").val(posts_limit);
            container.find("input[name*='wpp_enable']").val(wpp_enable);
        },
        changePriceOptions: function (e, self, variablePriceContainerTag) {
            var container = variablePriceContainerTag,
                posts_limit = container.find("input[name*='posts_limit']").val(),
                wpp_enable = container.find("input[name*='wpp_enable']").val();

            if (wpp_enable == 'yes') {
                $('#enable_wpp').prop('checked', true);
            } else {
                $('#enable_wpp').prop('checked', false);
            }

            $("#wpp_posts_limit").val(posts_limit);
            WPP.showHidePricingOptions();
        },
        showHidePricingOptions: function () {
            //Hide and show roles multi select menu
            var self = $('#enable_wpp:checked');
            if (self.val() === 'yes') {
                $(".wpp-options").show();
            } else {
                $(".wpp-options").hide();
            }
        },
        refresh: function () {
        }
    };
    WPP.init();
});
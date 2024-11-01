jQuery(document).ready(function ($) {
    var WPP = {
        init: function () {
            $('a.wpp-plus').off('click').on('click', this.addProduct);
            $('a.wpp-minus').off('click').on('click', this.deleteProduct);
            $("#wpp-products select[name*='product_id']").on('change', this.getProductSubIds);
            this.chosen();
            this.showHidePremiumOptions();
            $("#wpp_enable").on('click', this.showHidePremiumOptions);

            this.showHideFeaturedOptions();
            $("#wpp-featured").on('click', this.showHideFeaturedOptions);
        },
        showHidePremiumOptions: function () {
            var self = $('#wpp_enable:checked');
            if (self.val() === 'on') {
                $(".wpp-options").show();
            } else {
                $(".wpp-options").hide();
            }

            WPP.showHideFeaturedOptions();
        },
        showHideFeaturedOptions: function () {
            var premiumOptions = $('#wpp_enable:checked'),
                self = $('#wpp-featured:checked');
            if ((self.val() === 'on') && (premiumOptions.val() === 'on')) {
                $(".featured-options").show();
            } else {
                $(".featured-options").hide();
            }
        },
        addProduct: function (e) {
            e.preventDefault();
            var $this = $(this),
                table = $('table#wpp-products'),
                tr = $this.closest("tr"),
                selected = tr.find("select[name*='product_id']").val(),//In order to select the same selected value
                clone = tr.clone(),
                maxIndex = WPP.getMaxRowIndex(table);
            clone.find("select").each(function () {
                var self = $(this);
                self.attr({
                    'name': WPP.newInputName(self, maxIndex)
                });
            }).end().appendTo(table);
            clone.find("div.chosen-container").remove();
            clone.find("select[name*='product_id']").val(selected);
            $('a.wpp-plus').off('click').on('click', WPP.addProduct);
            $('a.wpp-minus').off('click').on('click', WPP.deleteProduct);
            $("#wpp-products select[name*='product_id']").on('change', WPP.getProductSubIds);
            WPP.chosen();
        },
        getProductSubIds: function () {
            var self = $(this),
                nonce = $("#wpp").val(),
                tr = self.closest("tr"),
                productId = self.find(":selected").val(),
                subIdsElement = tr.find("select[name*='product_sub_ids']"),
                loader = tr.find(".fms-cond-logic-loading"),
                params = {action: "wpp_get_product_sub_ids", wppNonce: nonce, productId: productId},
                data = $.param(params);

            loader.removeClass('hide');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function (result) {
                    loader.addClass('hide');
                    //in case we got unexpected result then just extract our json string
                    var extractJsonString = result.match(/\[tajer_json\](\{.+\})\[\/tajer_json\]/);
                    result = $.parseJSON(extractJsonString[1]);

                    //console.log(result);
                    subIdsElement.html(result.subIds);
                    //TajerPostType.Chosen();
                    $('.wpp-chosen').trigger("chosen:updated");
                }
            });
        },
        getMaxRowIndex: function (table) {
            var count = table.find('tr').length;
            return count += 1;
        },
        newInputName: function (self, maxIndex) {
            var currentName = self.attr("name");
            if (currentName.indexOf("tajer") >= 0) {
                //just replace the number in the name by a new number
                return currentName.replace(/\d+/, maxIndex);
            }
            return currentName;
        },
        deleteProduct: function (e) {
            e.preventDefault();
            var rowCount = $("table#wpp-products tr").length,
                self = $(this);
            if (rowCount > 1) {
                if (confirm('Are you sure?')) {
                    self.closest('tr').remove();
                }
            }
        },
        chosen: function () {

            $('.wpp-chosen').chosen({
                width: "100%"
            });

        },
        refresh: function () {
        }
    };
    WPP.init();
});
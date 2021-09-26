/**
 * This file is part of the Flash Sale plugin
 *
 * Copyright(c) ECCUBE VN LAB. All Rights Reserved.
 *
 * https://www.facebook.com/groups/eccube.vn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
var Condition = function () {
    var params;
    var productsClassData;
    var dataCategory = null;
    var categoryNameAndId = {};
    var conditionAddedValue = {};

    var _minus = '<i class="fas fa-minus fa-lg font-weight-bold text-secondary"></i>';
    var _plus = '<i class="fa fa-plus fa-lg font-weight-bold text-secondary"></i>';

    var ruleForm = $('#ruleForm');
    var addProduct = '#addProduct';
    var addProductCategory = $('#addProductCategory');
    var mdAddCondition = $('.mdAddCondition');

    return {
        init: function (_params) {
            params = _params;
            this.events();
        },
        events: function () {
            // Product search popup event - show
            $(addProduct).on('shown.bs.modal', function () {
                var rows = $(addProduct).find('table tbody tr');
                if (rows.length > 0) {
                    $.each(rows, function () {
                        Condition.handlePlusButton($(this));
                    });
                }
            });

            // Button search product in popup
            $(addProduct).on('click', '#searchProductModalButton', function () {
                var list = $('.searchDataModalList', $(this).closest('.modal-body'));
                $.ajax({
                    url: params.admin_order_search_product,
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        'id': $('#admin_search_product_id').val(),
                        'category_id': $('#admin_search_product_category_id').val()
                    }
                }).done(function (data) {
                    list.html(data);
                    productsClassData = productsClassCategories;

                    var _button = list.find('table tbody tr td button');
                    Condition.searchComplete(_button);
                }).fail(function () {
                    alert('Search product failed.');
                });
            });

            // ページング処理
            $(addProduct).on('click', '.searchDataModalList ul.pagination li.page-item a.page-link', function (e) {
                e.preventDefault();
                var list = $('.searchDataModalList', $(this).closest('.modal-body'));
                list.children().remove();
                var url = $(this).attr('href');
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html'
                }).done(function (data) {
                    list.html(data);
                    productsClassData = productsClassCategories;
                    var _button = list.find('table tbody tr td button');
                    Condition.searchComplete(_button);
                }).fail(function () {
                    alert('Search product failed.');
                });
            });

            // Button plus/minus item on product search popup
            $(addProduct).on('click', 'table > tbody > tr > td > button.btn-ec-actionIcon', function (e) {
                var $sele1 = $(this).closest('tr').find('select[name=classcategory_id1]');
                var $sele2 = $(this).closest('tr').find('select[name=classcategory_id2]');
                if ($sele1.length && $sele1.val() == '__unselected') {
                    alert(params.msg_unselected_class);
                    return;
                }

                if ($sele2.length && !$sele2.val()) {
                    alert(params.msg_unselected_class);
                    return;
                }

                var ProductClassId = Condition.fnAddConditionsProductClass($(this).closest('tr'), $(this).data('id'));
                if (ProductClassId == undefined) {
                    return;
                }

                Condition.handleInputProductSearchPopup(ProductClassId);
                Condition.handlePlusButton($(this).closest('tr'));
                Condition.handleProductClassName($(this).closest('tr'), $sele1, $sele2, ProductClassId);
            });

            // Detect (product search or category) popup to show
            ruleForm.on('click', '.findConditionsIds', function (e) {
                e.preventDefault();

                $(this).closest('td').find('.onFocus').removeClass('onFocus');
                $(this).closest('.condition-entity').addClass('onFocus');

                var conditionType = $(this).closest('.onFocus').find('[name="condition[type]"]').val();
                var dataIds = $(this).closest('.onFocus').find('[name="condition[value]"]').val();
                var mdAddCondition = $('.mdAddCondition');
                var inputConditionId = mdAddCondition.find('.inputConditionId');

                if (inputConditionId.length == 0) {
                    mdAddCondition.find('.searchDataModalList').before('<input type="hidden" class="inputConditionId form-control mb-2" value="' + dataIds + '">');
                } else {
                    inputConditionId.val(dataIds);
                }

                if (conditionType == 'condition_product_class_id') {
                    $(addProduct).modal('show');
                }

                if (conditionType == 'condition_product_category_id') {
                    Condition.getProductsCategory();
                }
            });

            // Click to remove item (product/category) name in condition
            ruleForm.on('click', '.condition-entity .nameList li a', function (e) {
                e.preventDefault();
                Condition.removeIdFromInputCondition($(this).closest('li'));
            });

            // Product category popup event - show
            addProductCategory.on('shown.bs.modal', function () {
                var valueIdInput = $('.mdAddCondition.show').find('input.inputConditionId').val();
                var tempArr = valueIdInput.split(',');
                $.each(tempArr, function (k, v) {
                    $("#addProductCategory").find('input[value="' + v + '"]').prop('checked', true);
                });
            });

            // Click check/uncheck checkbox on category popup
            addProductCategory.on('click', 'input[type="checkbox"]', function (e) {
                var catId = $(this).val();
                Condition.handleInputCategoryPopup(catId);
            });

            // TODO: keep old value when change condition of select options
            /*ruleForm.on('click', '[name="condition[type]"]', function () {
                var _selCondition = $(this);
                var _selConditionType = $("option:selected", $(this)).val();
                var conditionEntity = _selCondition.closest('.condition-entity');
                var conditionEntityId = conditionEntity.attr('data-id-temp');
                if(conditionEntityId == undefined){
                    conditionEntityId = Math.random().toString(36).substring(2);
                    conditionEntity.attr('data-id-temp', conditionEntityId);
                }
                var valueAdded = {
                    'cKey' : _selConditionType,
                    'cValue': conditionEntity.find('[name="condition[value]"]').val(),
                    'cListName' : conditionEntity.find('.nameList').html()
                };
                conditionAddedValue[conditionEntityId] =  valueAdded;
                console.log(conditionAddedValue);
            });*/
            ruleForm.on('change', '[name="condition[type]"]', function () {
                $(this).closest('.condition-entity').find('.nameList').html('');
                $(this).closest('.condition-entity').find('[name="condition[value]"]').val('');
            });

            // Event change select category 1
            var sel1 = mdAddCondition.find('select[name="classcategory_id1"]');
            $.each(sel1, function () {
                $(this).on('change', function () {
                    var rowTr = $(this).closest('tr');
                    Condition.handlePlusButton(rowTr);
                });
            });
        },
        handleProductClassName: function (_item, $sele1, $sele2, ProductClassId) {
            var productName = _item.find('td:nth-child(2) > p.m-0').text();
            var name_cat1 = '',
                name_cat2 = '';

            if ($sele1 != undefined && $("option:selected", $sele1).text()) {
                name_cat1 = $("option:selected", $sele1).text();
            }
            if ($sele2 != undefined && $("option:selected", $sele2).text()) {
                name_cat2 = ' - ' + $("option:selected", $sele2).text();
            }
            if (name_cat1 || name_cat2) {
                productName += ' (' + name_cat1 + name_cat2 + ')';
            }

            var nameList = ruleForm.find('.condition-entity.onFocus .nameList');
            var inputValue = ruleForm.find('div.onFocus input[name="condition[value]"]').val().split(',');
            if ($.inArray(ProductClassId, inputValue) !== -1) {
                ruleForm.find('.condition-entity.onFocus .nameList').append('<li data-id="' + ProductClassId + '"><a href="#"><i class="fas fa-times"></i></a> ' + productName + '</li>');
            } else {
                nameList.find('li[data-id="' + ProductClassId + '"]').slideUp("normal", function () {
                    $(this).remove();
                });
            }
        },
        removeIdFromInputCondition: function (_item) {
            var id = _item.data('id');
            var newInputValue = [];
            var contentUl = _item.closest('ul').find('li');
            $.each(contentUl, function () {
                if($(this).data('id') != id){
                    newInputValue.push($(this).data('id'));
                }
            });
            _item.closest('.condition-entity').find('input[name="condition[value]"]').val(newInputValue.toString());
            _item.slideUp("normal", function () {
                $(this).remove();
            });
        },
        addCategoriesToObject: function () {
            if ($.isEmptyObject(categoryNameAndId)) {
                var allCategories = addProductCategory.find('.searchDataModalList input');
                if (allCategories.length > 0) {
                    $.each(allCategories, function () {
                        var catId = $(this).val();
                        var catName = $(this).closest('li').find('> label[for="product-category-' + catId + '"]').text();
                        categoryNameAndId[catId] = catName;
                    });
                }
            }
        },
        getProductsCategory: function () {
            if (dataCategory === null) {
                $.ajax({
                    url: addProductCategory.data('url'),
                    type: 'GET',
                    dataType: 'html'
                }).done(function (data) {
                    dataCategory = data;
                    addProductCategory.find('.searchDataModalList').html(dataCategory);
                    addProductCategory.modal('show');
                    Condition.addCategoriesToObject();
                }).fail(function () {
                    alert('Search category failed.');
                });
            } else {
                addProductCategory.find('.searchDataModalList').html(dataCategory);
                addProductCategory.modal('show');
            }
        },
        renderCategoryNameData: function (newInputValue) {
            var nameList = ruleForm.find('.condition-entity.onFocus .nameList');
            nameList.html('');
            $.each(newInputValue, function (k, id) {
                if (id && categoryNameAndId[id]) {
                    nameList.append('<li data-id="' + id + '"><a href="#"><i class="fas fa-times"></i></a> ' + categoryNameAndId[id] + '</li>');
                }
            });
        },
        handleInputProductSearchPopup: function (dataId) {
            if (dataId == undefined) {
                return;
            }

            var valueIdInput = $('.mdAddCondition.show').find('input.inputConditionId');
            var newInputValue = Condition.calculatorInput(valueIdInput, dataId);
            Condition.setInputData(newInputValue);
        },
        handleInputCategoryPopup: function (dataId) {
            if (dataId == undefined) {
                return;
            }

            var valueIdInput = $('.mdAddCondition.show').find('input.inputConditionId');
            var newInputValue = Condition.calculatorInput(valueIdInput, dataId);
            Condition.setInputData(newInputValue);
            Condition.renderCategoryNameData(newInputValue.split(','));
        },
        calculatorInput: function (valueIdInput, id) {
            var inputValue = valueIdInput.val().split(',');
            if ($.inArray(id, inputValue) !== -1) {
                var result = inputValue.filter(function (elem) {
                    return (elem != id && elem != '');
                });
                return result.toString();
            } else {
                return valueIdInput.val() ? (valueIdInput.val() + ',' + id) : id;
            }
        },
        setInputData: function (newValue) {
            $('.mdAddCondition.show').find('input.inputConditionId').val(newValue);
            $('#ruleForm').find('div.onFocus input[name="condition[value]"]').val(newValue);
        },
        searchComplete: function (_button) {
            if (_button != undefined) {
                $.each(_button, function () {
                    var dataAttr = $(this).attr('onclick');
                    var dataIds = dataAttr.split(',');
                    $(this).attr('data-id', $.trim(dataIds[1]));
                    $(this).attr('data-type', $.trim(dataIds[2]));
                    $(this).attr('data-action', 'plus');
                    $(this).removeAttr('onclick');
                });
            }

            var sel1 = mdAddCondition.find('select[name="classcategory_id1"]');
            $.each(sel1, function () {
                $(this).on('change', function () {
                    var rowTr = $(this).closest('tr');
                    Condition.handlePlusButton(rowTr);
                });
            });

            var sel2 = mdAddCondition.find('select[name="classcategory_id2"]');
            $.each(sel2, function () {
                $(this).on('change', function () {
                    var rowTr = $(this).closest('tr');
                    Condition.handlePlusButton(rowTr);
                });
            });

            var rows = $(addProduct).find('table tbody tr');
            if (rows.length > 0) {
                $.each(rows, function () {
                    Condition.handlePlusButton($(this));
                });
            }
        },
        handlePlusButton: function ($row) {
            var btnPlus = $row.find('.btn-ec-actionIcon');
            var ProductClassId = Condition.fnAddConditionsProductClass($row, btnPlus.data('id'));
            if (ProductClassId != undefined) {
                var inputConditionId = ruleForm.find('.condition-entity.onFocus input[name="condition[value]"]');
                var currentValue = inputConditionId.val().split(',');
                if ($.inArray(ProductClassId, currentValue) !== -1) {
                    btnPlus.attr('data-action', 'minus').html(_minus);
                    return;
                }
            }

            btnPlus.attr('data-action', 'plus').html(_plus);
            return;
        },
        fnAddConditionsProductClass: function ($row, product_id) {
            var product,
                class_category_id1,
                class_cateogry_id2;
            var $sele1 = $row.find('select[name=classcategory_id1]');
            var $sele2 = $row.find('select[name=classcategory_id2]');
            var product_class_id = null;
            if (!$sele1.length && !$sele2.length) {
                product = productsClassData[product_id]['__unselected2']['#'];
                product_class_id = product['product_class_id'];
            } else if ($sele1.length) {
                if ($sele2.length) {
                    class_category_id1 = $sele1.val();
                    class_cateogry_id2 = $sele2.val();
                    if (class_category_id1 == '__unselected' || !class_cateogry_id2) {
                        return;
                    }
                    product = productsClassData[product_id][class_category_id1]['#' + class_cateogry_id2];
                    product_class_id = product['product_class_id'];
                } else {
                    class_category_id1 = $sele1.val();
                    if (class_category_id1 == '__unselected') {
                        return;
                    }
                    product = productsClassData[product_id][class_category_id1]['#'];
                    product_class_id = product['product_class_id'];
                }
            }

            return product_class_id;
        }
    }
}();
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

(function($){
    $.fn.initRulePanel = function (options) {
        var settings = $.extend({
            setup: {
                rules_types: {}
            }
        }, options);
        var self = $(this), fn = this;

        this.changeRule = function (e) {
            var value = $(e.target).val(),
                container = $(e.target).closest('.rule-entity');

            container.find('select[name="rule[operator]"]').html('');
            $.each(settings.setup.rule_types[value].operator_types, function(key, operator) {
                container.find('[name="rule[operator]"]')
                    .append('<option value="'+key+'">'+ operator.name +'</option>');
            });

            container.find('select[name="promotion[type]"]').html('');
            container.find('[name="promotion[value]"]').val('');
            $.each(settings.setup.rule_types[value].promotion_types, function(key, promotion) {
                container.find('[name="promotion[type]"]').append('<option value="'+key+'">'+ promotion.name +'</option>');
            });

            $.each(container.find('.condition-entity'), function(key, conditionContainer) {
                $(conditionContainer).find('[name="condition[type]"]').html('');
                $(conditionContainer).find('[name="condition[operator]"]').html('');
                if(value == 'rule_cart'){
                    $(conditionContainer).find('[name="condition[value]"]').val('').attr('type', 'text');
                }else{
                    $(conditionContainer).find('[name="condition[value]"]').val('').attr('type', 'hidden');
                }

                $.each(settings.setup.rule_types[value].condition_types, function(key, condition) {
                    $(conditionContainer).find('[name="condition[type]"]').append('<option value="'+key+'">'+ condition.name +'</option>');
                    $.each(condition.operator_types, function(key, operator) {
                        // TODO: It's will render duplicate condition operator (temporary fix)
                        if($(conditionContainer).find('[name="condition[operator]"] option[value="'+key+'"]').length == 0){
                            $(conditionContainer).find('[name="condition[operator]"]').append('<option value="'+key+'">'+ operator.name +'</option>');
                        }
                    });
                });
            });

            if (value == 'rule_cart') {
                container.find('.nameList').addClass('d-none');
                container.find('.findConditionsIds').addClass('d-none');
            } else {
                container.find('.nameList').removeClass('d-none').html('');
                container.find('.findConditionsIds').removeClass('d-none');
            }
        };

        this.addRule = function(e) {
            var data = $.extend({
                id: '',
                type: '',
                operator: '',
                promotion: {
                    type: '',
                    value: ''
                }
            }, e);
            var ruleId = (data.id ? data.id : self.find('.rule-entity').length);
            var template = self.find('[data-template="rule"]').clone()
                .removeClass('d-none')
                .addClass('rule-entity')
                .attr('data-template', '')
                .attr('id', 'rule' + ruleId)
                .attr('data-template-name', Math.random().toString(36).substring(2));
            $.each(settings.setup.rule_types, function(key) {
                $(template).find('[name="rule[type]"]').append('<option value="'+key+'" ' + (data.type === key ? 'selected' : '') +'>'+ this.name +'</option>');
            });
            $(template).find('[name="rule[id]"]').val(data.id);

            var ruleType = $(template).find('[name="rule[type]"]').val();
            $.each(settings.setup.rule_types[ruleType].operator_types, function(key, operator) {
                $(template).find('[name="rule[operator]"]').append('<option value="'+key+'" '+ (data.operator === key ? 'selected' : '') +'>'+ operator.name +'</option>');
            });
            $.each(settings.setup.rule_types[ruleType].promotion_types, function(key) {
                $(template).find('[name="promotion[type]"]').append('<option value="'+key+'" '+ (data.promotion.type === key ? 'selected' : '') +'>'+ this.name +'</option>');
                $(template).find('[name="promotion[value]"]').val(data.promotion.value);
                $(template).find('[name="promotion[id]"]').val(data.promotion.id);
            });

            if (data.type == 'rule_cart') {
                template.find('.nameList').addClass('d-none');
                template.find('.findConditionsIds').addClass('d-none');
                template.find('[name="condition[value]"]').attr('type', 'text');
            } else {
                template.find('.nameList').removeClass('d-none');
                template.find('.findConditionsIds').removeClass('d-none');
                template.find('[name="condition[value]"]').attr('type', 'hidden');
            }
            self.find('[data-container="rule"]').append(template);
        };

        this.addCondition = function (e) {
            var data = $.extend({
                id: '',
                type: '',
                attribute: '',
                operator: '',
                value: ''
            }, e);
            var container = $(e.target).closest('.condition').find('[data-container="condition"]');
            var template = container.find('[data-template="condition"]').clone()
                .removeClass('d-none')
                .addClass('condition-entity')
                .attr('data-template', '')
                .attr('id', 'condition' + (data.id ? data.id : self.find('.condition').length));

            var ruleType = $(e.target).closest('.rule-entity').find('[name="rule[type]"]').val();
            var conditionData = JSON.parse($('#conditionData').val());

            $.each(settings.setup.rule_types[ruleType]['condition_types'], function (key, condition) {
                var nameHtml = '';
                var dataValueArr = data.value.split(',');
                $.each(dataValueArr, function (dKey, dValue) {
                    $.each(conditionData[data.type], function (cKey, cData) {
                        if (dValue == cData.id) {
                            nameHtml += '<li data-id="' + cData.id + '"><a href="#"><i class="fas fa-times"></i></a> ' + cData.name + '</li>';
                        }
                    });
                });

                $(template).find('[name="condition[type]"]').append('<option value="' + key + '" ' + (data.type === key ? 'selected' : '') + '>' + condition.name + '</option>');
                $(template).find('[name="condition[value]"]').val(data.value);
                $(template).find('.nameList').html(nameHtml);
                $(template).find('[name="condition[id]"]').val(data.id);
            });
            var conditionType = $(template).find('[name="condition[type]"]').val();
            $.each(settings.setup.rule_types[ruleType]['condition_types'][conditionType].operator_types, function (key, operator) {
                $(template).find('[name="condition[operator]"]').append('<option value="' + key + '" ' + (data.operator === key ? 'selected' : '') + '>' + operator.name + '</option>');
            });

            if (ruleType == 'rule_cart') {
                $(template).find('.nameList').addClass('d-none');
                $(template).find('.findConditionsIds').addClass('d-none');
                $(template).find('[name="condition[value]"]').attr('type', 'text');
            } else {
                $(template).find('.nameList').removeClass('d-none');
                $(template).find('.findConditionsIds').removeClass('d-none');
                $(template).find('[name="condition[value]"]').attr('type', 'hidden');
            }
            container.append(template);
        };

        this.removeRule = function (e) {
            $(e.target).closest('.rule-entity').remove();
        };

        this.removeCondition = function (e) {
            $(e.target).closest('.condition-entity').remove();
        };

        function bind() {
            self.find('[data-bind="addRule"]').on('click', fn.addRule);
            self.closest('form').on('change', '[data-bind="changeRule"]', fn.changeRule);
            self.closest('form').on('click', '[data-bind="removeRule"]', fn.removeRule);
            self.closest('form').on('click', '[data-bind="addCondition"]', fn.addCondition);
            self.closest('form').on('click', '[data-bind="removeCondition"]', fn.removeCondition);

            self.closest('form').on('submit', function (e) {
                var rules = [];
                $.each($(this).find('.rule-entity'), function () {
                    var rule = {};
                    rule.id = $(this).find('[name="rule[id]"]').val();
                    rule.type = $(this).find('[name="rule[type]"]').val();
                    rule.operator = $(this).find('[name="rule[operator]"]').val();
                    rule.promotion = {};
                    rule.promotion.id = $(this).find('[name="promotion[id]"]').val();
                    rule.promotion.type = $(this).find('[name="promotion[type]"]').val();
                    rule.promotion.attribute = $(this).find('[name="promotion[attribute]"]').val();
                    rule.promotion.value = $(this).find('[name="promotion[value]"]').val();
                    rule.conditions = [];
                    $.each($(this).find('.condition-entity'), function () {
                        var condition = {};
                        condition.id = $(this).find('[name="condition[id]"]').val();
                        condition.type = $(this).find('[name="condition[type]"]').val();
                        condition.attribute = $(this).find('[name="condition[attribute]"]').val();
                        condition.operator = $(this).find('[name="condition[operator]"]').val();
                        condition.value = $(this).find('[name="condition[value]"]').val();
                        rule.conditions.push(condition);
                    });
                    rules.push(rule);
                });
                $(this).find('[name*="[rules]"]').val(JSON.stringify(rules));
            });
        }

        function render() {
            var rules = JSON.parse(self.find('[name*="[rules]"]').val());
            $.each(rules, function (k) {
                fn.addRule(rules[k]);
                $.each(rules[k]['conditions'], function (i) {
                    var condition = rules[k]['conditions'][i];
                    var id = self.find('[data-container="rule"] .rule-entity:last').attr('id');
                    condition.target = $('#' + id + ' [data-bind="addCondition"]')[0];
                    fn.addCondition(condition);
                });
            });
        }

        function initialize() {
            render();
            bind();
        }
        initialize();
        return this;
    }
}(jQuery));
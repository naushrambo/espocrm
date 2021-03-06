/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/


Espo.define('Views.Admin.Layouts.Modals.EditAttributes', ['Views.Modal', 'Model'], function (Dep, Model) {

    return Dep.extend({

        _template: '<div class="edit-container">{{{edit}}}</div>',

        setup: function () {
            this.buttons = [
                {
                    name: 'save',
                    text: this.translate('Apply'),
                    style: 'primary',
                    onClick: function (dialog) {
                        this.save();
                    }.bind(this)
                },
                {
                    name: 'cancel',
                    text: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                }
            ];

            var model = new Model();
            model.name = 'LayoutManager';
            model.set(this.options.attributes || {});

            this.header = this.translate(this.options.name, 'fields', this.options.scope);

            var attributeList = Espo.Utils.clone(this.options.attributeList || []);
            var index = attributeList.indexOf('name');
            if (~index) {
                attributeList.splice(index, 1);
            }

            this.createView('edit', 'Admin.Layouts.Record.EditAttributes', {
                el: this.options.el + ' .edit-container',
                attributeList: attributeList,
                attributeDefs: this.options.attributeDefs,
                model: model
            });
        },

        save: function () {
            var editView = this.getView('edit');
            var attrs = editView.fetch();

            editView.model.set(attrs, {silent: true});
            if (editView.validate()) {
                return;
            }

            var attributes = {};
            attributes = editView.model.attributes;

            this.trigger('after:save', attributes);
            return true;
        },
    });
});

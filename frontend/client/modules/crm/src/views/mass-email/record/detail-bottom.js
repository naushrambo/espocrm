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


Espo.define('crm:views/mass-email/record/detail-bottom', 'views/record/detail-bottom', function (Dep) {

    return Dep.extend({

        setupPanels: function () {
            Dep.prototype.setupPanels.call(this);

            this.panelList.unshift({
                name: 'queueItems',
                label: this.translate('queueItems', 'links', 'MassEmail'),
                view: 'views/record/panels/relationship',
                select: false,
                create: false,
                layout: 'listForMassEmail',
                rowActionsView: 'views/record/row-actions/empty',
                filterList: ['all', 'pending', 'sent', 'failed']
            });
        },

        afterRender: function () {
            Dep.prototype.setupPanels.call(this);
        }

    });
});



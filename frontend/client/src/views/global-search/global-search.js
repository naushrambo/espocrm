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

Espo.define('Views.GlobalSearch.GlobalSearch', 'View', function (Dep) {

    return Dep.extend({

        template: 'global-search.global-search',

        events: {
            'keypress #global-search-input': function (e) {
                if (e.keyCode == 13) {
                    this.runSearch();
                }
            },
            'click [data-action="search"]': function () {
                this.runSearch();
            },
            'focus #global-search-input': function (e) {
                e.currentTarget.select();
            }
        },

        setup: function () {

            this.wait(true);
            this.getCollectionFactory().create('GlobalSearch', function (collection) {
                this.collection = collection;
                collection.name = 'GlobalSearch';
                this.wait(false);
            }, this);

        },

        afterRender: function () {
            this.$input = this.$el.find('#global-search-input');
        },

        runSearch: function (text) {
            var text = this.$input.val();
            if (text != '' && text.length > 2) {
                text = encodeURI(text);
                this.search(text);
            }
        },

        search: function (text) {
            this.collection.url = this.collection.urlRoot =  'GlobalSearch/' + text;

            this.showPanel();
        },

        showPanel: function () {
            this.closePanel();

            var $container = $('<div>').attr('id', 'global-search-panel');

            $container.appendTo(this.$el.find('.global-search-panel-container'));

            this.createView('panel', 'GlobalSearch.Panel', {
                el: '#global-search-panel',
                collection: this.collection,
            }, function (view) {
                view.render();
            }.bind(this));

            $document = $(document);
            $document.on('mouseup.global-search', function (e) {
                if (e.target.tagName == 'A' && $(e.target).data('action') != 'showMore') {
                    setTimeout(function () {
                        this.closePanel();
                    }.bind(this), 100);
                    return;
                }
                 if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                    this.closePanel();
                   }
            }.bind(this));
        },

        closePanel: function () {
            $container = $('#global-search-panel');

            $('#global-search-panel').remove();
            $document = $(document);
            if (this.hasView('panel')) {
                this.getView('panel').remove();
            };
             $document.off('mouseup.global-search');
               $container.remove();
        },

    });

});


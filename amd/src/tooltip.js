// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * File:        tooltip.js
 * Author:      Osvaldas Valutis (http://osvaldas.info/)
 * Info:        http://osvaldas.info/elegant-css-and-jquery-tooltip-responsive-mobile-friendly
 *
 * Copyright Osvaldas Valutis.
 *
 * This source file is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the MIT license for details: http://opensource.org/licenses/MIT.
 */
define(['jquery'], function($) {
    return {
        init: function() {
            $(document).ready(function () {
                var targets = $('[rel~=tooltip]');

                targets.bind('mouseenter', function () {
                    const target = $(this),
                        tip = target.attr('title'),
                        tooltip = $('<div id="tooltip2"></div>');

                    if (!tip || tip == '') {
                        return false;
                    }

                    target.removeAttr('title');
                    tooltip.css('opacity', 0)
                        .html(tip)
                        .appendTo('body');

                    const init_tooltip = () => {
                        if ($(window).width() < tooltip.outerWidth() * 1.5) {
                            tooltip.css('max-width', $(window).width() / 2);
                        } else {
                            tooltip.css('max-width', 340);
                        }

                        var pos_left = target.offset().left + (target.outerWidth() / 2) - (tooltip.outerWidth() / 2),
                            pos_top = target.offset().top - tooltip.outerHeight() - 20;

                        if (pos_left < 0) {
                            pos_left = target.offset().left + target.outerWidth() / 2 - 20;
                            tooltip.addClass('left');
                        } else {
                            tooltip.removeClass('left');
                        }

                        if (pos_left + tooltip.outerWidth() > $(window).width()) {
                            pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
                            tooltip.addClass('right');
                        } else {
                            tooltip.removeClass('right');
                        }

                        if (pos_top < 0) {
                            pos_top = target.offset().top + target.outerHeight();
                            tooltip.addClass('top');
                        } else {
                            tooltip.removeClass('top');
                        }

                        tooltip.css({left: pos_left, top: pos_top})
                            .animate({top: '+=10', opacity: 1}, 50);
                    };

                    init_tooltip();
                    $(window).resize(init_tooltip);

                    const remove_tooltip = () => {
                        tooltip.animate({top: '-=10', opacity: 0}, 50, () => {
                            tooltip.remove();
                        });

                        target.attr('title', tip);
                    };

                    target.bind('mouseleave', remove_tooltip);
                    tooltip.bind('click', remove_tooltip);
                });
            });
        }
    };
});

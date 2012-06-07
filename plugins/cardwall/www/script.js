document.observe('dom:loaded', function () {
    $$('.cardwall_board').each(function (board) {
        //{{{ Make sure that we got the last version of the card wall
        if ($('tracker_report_cardwall_to_be_refreshed')) {
            //    Eg: board > drag n drop > go to a page > click back (the post it should be drag 'n dropped)
            if ($F('tracker_report_cardwall_to_be_refreshed') === "1") {
                $('tracker_report_cardwall_to_be_refreshed').value = 0;
                location.reload();
            } else {
                $('tracker_report_cardwall_to_be_refreshed').value = 1;
            }
        }
        // }}}

        // {{{ Define the draggables cards
        board.select('.cardwall_board_postit').each(function (postit) {
            var d = new Draggable(postit, {
                revert: 'failure'
            });
        });
        // }}}

        // {{{ Define the droppables columns
        var cols = board.select('col'),
            i = 0,
            cols_classnames = [];
        cols.each(function (col) {
            var c = board.identify() + '_dummy_' + i;
            cols_classnames[i] = c;
            col.up('table').down('tbody tr').down('td', col.up().childElements().indexOf(col)).select('.cardwall_board_postit').invoke('addClassName', c);
            i = i + 1;
        });
        cols.each(function (col) {
            var col_index = cols.indexOf(col),
                td = col.up('table').down('tbody tr').down('td', col_index),
                effect = null,
                restorecolor = td.getStyle('background-color');
            Droppables.add(td, {
                hoverclass: 'cardwall_board_column_hover',
                accept: cols_classnames.reject(function (value, key) {
                    return key === col_index;
                }),
                onDrop: function (dragged, dropped, event) {
                    //change the classname of the post it to be accepted by the formers columns
                    dragged.removeClassName(cols_classnames[dragged.up('tr').childElements().indexOf(dragged.up('td'))]);
                    dragged.addClassName(cols_classnames[col_index]);

                    //switch to the new column
                    Element.remove(dragged);
                    td.down('ul').appendChild(dragged);
                    dragged.setStyle({
                        left: 'auto',
                        top: 'auto'
                    });
                    if (effect) {
                        effect.cancel();
                    }
                    effect = new Effect.Highlight(td, {
                        restorecolor: restorecolor
                    });

                    //save the new state
                    var parameters = {
                            aid: dragged.id.split('-')[1],
                            func: 'artifact-update'
                        },
                        field_id,
                        value_id = col.id.split('-')[1];
                    if ($('tracker_report_cardwall_settings_column')) {
                         field_id = $F('tracker_report_cardwall_settings_column');
                    } else {
                        console.log(field_id, value_id);
                        field_id = dragged.readAttribute('data-column-field-id');
                        value_id = $F('cardwall_column_mapping_' + value_id + '_' + field_id);
                        console.log(field_id, value_id);
                    }
                    parameters['artifact[' + field_id + ']'] = value_id;
                    var req = new Ajax.Request(codendi.tracker.base_url, {
                        method: 'POST',
                        parameters: parameters,
                        onComplete: function () {
                            //TODO handle errors (perms, workflow, ...)
                            // eg: change color of the post it
                        }
                    });
                }
            });
        });
        // }}}
    });
});

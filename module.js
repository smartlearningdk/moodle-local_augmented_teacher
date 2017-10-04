M.local_augmented_teacher = {};

M.local_augmented_teacher.init_taskselection = function(Y) {
    Y.on('change', function () {
        var action = Y.one('#formactionid');
        if (action.get('value') == '') {
            return;
        }
        Y.one('#taskselectionform').submit();
    }, '#formactionid');
};

M.local_augmented_teacher.init_participation = function(Y) {
    Y.on('submit', function(evt) {
        var ok = false;
        Y.all('input.usercheckbox').each(function() {
            if (this.get('checked')) {
                ok = true;
            }
        });
        if (!ok) {
            evt.preventDefault();
            return;
        }
    }, '#participantsform');

    Y.on('click', function(e) {
        var showallink = this.getAttribute('data-showallink');
        if (showallink) {
            window.location = showallink;
        }
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', 'checked');
        });
    }, '#checkall, #checkallonpage');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', '');
        });
    }, '#checknone');
};

M.local_augmented_teacher.init_shortcode = function(Y) {
    Y.on('click', function(e) {
        var text = this.get('text');
        e.preventDefault();

        var editorel = Y.one('#edit-messagebodyeditable');

        editorel.focus();

        var selectPastedContent = false;
        var sel, range;
        if (window.getSelection) {
            // IE9 and non-IE.
            sel = window.getSelection();
            if (sel.getRangeAt && sel.rangeCount) {
                range = sel.getRangeAt(0);
                range.deleteContents();

                var el = document.createElement("div");
                el.innerHTML = text;
                var frag = document.createDocumentFragment(), node, lastNode;
                while ( (node = el.firstChild) ) {
                    lastNode = frag.appendChild(node);
                }
                var firstNode = frag.firstChild;
                range.insertNode(frag);

                // Preserve the selection.
                if (lastNode) {
                    range = range.cloneRange();
                    range.setStartAfter(lastNode);
                    if (selectPastedContent) {
                        range.setStartBefore(firstNode);
                    } else {
                        range.collapse(true);
                    }
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }
        } else if ( (sel = document.selection) && sel.type != "Control") {
            // IE < 9
            var originalRange = sel.createRange();
            originalRange.collapse(true);
            sel.createRange().pasteHTML(text);
            if (selectPastedContent) {
                range = sel.createRange();
                range.setEndPoint("StartToStart", originalRange);
                range.select();
            }
        }
    }, '.shortcode');
};

M.local_augmented_teacher.init_tree = function(Y, expand_all, htmlid) {
    Y.use('yui2-treeview', function(Y) {
        var tree = new Y.YUI2.widget.TreeView(htmlid);

        tree.subscribe("clickEvent", function(node, event) {
            // we want normal clicking which redirects to url
            return false;
        });

        if (expand_all) {
            tree.expandAll();
        }

        tree.render();
    });
};
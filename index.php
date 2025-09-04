<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LDAP Browser</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding-top: 56px; /* Space for fixed navbar */
        }
        .container-fluid {
            padding-top: 20px;
        }
        #ldap-tree-panel {
            border-right: 1px solid #dee2e6;
            padding-right: 15px;
            min-height: 80vh;
            overflow-y: auto;
        }
        #attributes-panel {
            padding-left: 15px;
            min-height: 80vh;
            overflow-y: auto;
        }
        .ldap-entry {
            cursor: pointer;
            padding: 3px 0;
            padding-left: 20px;
            position: relative;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ldap-entry:hover {
            background-color: #e9ecef;
        }
        .ldap-entry.selected {
            background-color: #007bff;
            color: white;
        }
        .ldap-entry.collapsed::before {
            content: "\25B6"; /* Right-pointing triangle */
            position: absolute;
            left: 5px;
            font-size: 0.8em;
            top: 8px;
        }
        .ldap-entry.expanded::before {
            content: "\25BC"; /* Down-pointing triangle */
            position: absolute;
            left: 5px;
            font-size: 0.8em;
            top: 8px;
        }
        .ldap-entry.leaf::before {
            content: "\2022"; /* Bullet point */
            position: absolute;
            left: 5px;
            font-size: 0.8em;
            top: 8px;
        }
        .ldap-entry-text {
            margin-left: 15px; /* Space for the triangle/bullet */
        }
        .attribute-table th, .attribute-table td {
            word-break: break-all;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">LDAP Browser</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">Search</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <h1 class="mb-4">LDAP Browser</h1>
        <div class="row">
            <div class="col-md-5" id="ldap-tree-panel">
                <h3>LDAP Tree</h3>
                <div id="ldap-tree-root" class="ldap-entry expanded" data-dn="dc=AD,dc=UONBI,dc=AC,dc=KE">
                    <span class="ldap-entry-text">dc=AD,dc=UONBI,dc=AC,dc=KE</span>
                </div>
            </div>
            <div class="col-md-7" id="attributes-panel">
                <h3>Attributes</h3>
                <div id="attribute-display">
                    <p class="text-muted">Select an entry from the tree to view its attributes.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedElement = null;

            function loadChildren(element, callback) {
                const dn = element.data('dn');
                $.ajax({
                    url: 'browse.php',
                    type: 'POST',
                    data: { dn: dn },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            alert('Error loading children: ' + response.error);
                            return;
                        }
                        const childrenContainer = $('<div class="ms-4"></div>');
                        if (response.children && response.children.length > 0) {
                            response.children.forEach(function(entry) {
                                const childElement = $('<div class="ldap-entry collapsed leaf"></div>')
                                    .data('dn', entry.dn)
                                    .append($('<span class="ldap-entry-text"></span>').text(entry.dn));
                                childrenContainer.append(childElement);
                            });
                            element.removeClass('leaf'); // No longer a leaf if it has children
                        } else {
                            element.addClass('leaf'); // It's a leaf if no children found
                        }
                        element.append(childrenContainer);
                        if (callback) callback();
                    },
                    error: function(xhr, status, error) {
                        alert('Failed to load LDAP children data. Status: ' + status + ', Error: ' + error + ', Response: ' + xhr.responseText);
                    }
                });
            }

            function loadAttributes(element) {
                const dn = element.data('dn');
                if (selectedElement) {
                    selectedElement.removeClass('selected');
                }
                element.addClass('selected');
                selectedElement = element;

                $('#attribute-display').html('<p class="text-muted">Loading attributes...</p>');

                $.ajax({
                    url: 'browse.php',
                    type: 'POST',
                    data: { dn: dn, get_attributes: true },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            $('#attribute-display').html('<div class="alert alert-danger">Error loading attributes: ' + response.error + '</div>');
                            return;
                        }
                        if (response.attributes) {
                            let html = '<table class="table table-sm table-bordered attribute-table"><thead><tr><th>Attribute</th><th>Value</th></tr></thead><tbody>';
                            for (const attr in response.attributes) {
                                if (response.attributes.hasOwnProperty(attr)) {
                                    let value = response.attributes[attr];
                                    if (Array.isArray(value)) {
                                        value = value.join('<br>');
                                    }
                                    html += `<tr><td><strong>${attr}</strong></td><td>${value}</td></tr>`;
                                }
                            }
                            html += '</tbody></table>';
                            $('#attribute-display').html(html);
                        } else {
                            $('#attribute-display').html('<p class="text-muted">No attributes found for this entry.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#attribute-display').html('<div class="alert alert-danger">Failed to load LDAP attributes. Status: ' + status + ', Error: ' + error + ', Response: ' + xhr.responseText + '</div>');
                    }
                });
            }

            $('#ldap-tree-panel').on('click', '.ldap-entry', function(e) {
                e.stopPropagation();
                const element = $(this);
                const childrenContainer = element.children('div');

                loadAttributes(element); // Always load attributes on click

                if (element.hasClass('expanded')) {
                    element.removeClass('expanded').addClass('collapsed');
                    if (childrenContainer.length) {
                        childrenContainer.remove();
                    }
                } else if (element.hasClass('collapsed')) {
                    element.removeClass('collapsed').addClass('expanded');
                    // Only load children if they haven't been loaded yet
                    if (!childrenContainer.length) {
                        loadChildren(element, function() {
                            // After children are loaded, if it's still a leaf, add leaf class
                            if (element.children('div').children().length === 0) {
                                element.addClass('leaf');
                            }
                        });
                    }
                } else if (element.hasClass('leaf')) {
                    // If it's a leaf and clicked, just load attributes (already done above)
                    // No expansion/collapse for true leaves
                }
            });

            // Initial load for the root
            loadChildren($('#ldap-tree-root'), function() {
                // After initial children are loaded, select the root and load its attributes
                loadAttributes($('#ldap-tree-root'));
            });
        });
    </script>
</body>
</html>

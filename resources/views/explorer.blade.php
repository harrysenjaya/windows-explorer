<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Explorer</title>

    {{-- Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-5.3.8/css/bootstrap.min.css') }}">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        .folder-item {
            cursor: pointer;
        }

        .tree ul {
            list-style-type: disc;
            padding-left: 1rem;
        }
    </style>
</head>

<body class="vh-100">
    <div class="container-fluid h-100">
        <div class="row h-100">
            <div id="left-panel" class="col-12 col-md-4 border-end overflow-auto p-3">
                <h2 class="h5 mb-3"><i class="bi bi-folder2"></i> Folders</h2>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <button id="btnCreate" class="btn btn-sm btn-primary" disabled>
                        <i class="bi bi-folder-plus"></i> New Folder
                    </button>
                    <button id="btnRename" class="btn btn-sm btn-secondary" disabled>
                        <i class="bi bi-pencil"></i> Rename
                    </button>
                    <button id="btnDelete" class="btn btn-sm btn-danger" disabled>
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
                <div id="selectedInfo" class="small text-muted mb-2">No folder selected</div>
                <div id="tree" class="tree small"></div>
            </div>
            <div id="right-panel" class="col-12 col-md-8 overflow-auto p-3">
                <h2 class="h5 mb-3"><i class="bi bi-collection"></i> Subfolders</h2>
                <div id="subfolders" class="row g-3"></div>
                <hr class="my-3">
                <h2 class="h5 mb-3"><i class="bi bi-files"></i> Files</h2>
                <div id="files" class="row g-3"></div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="createName" class="form-label">Folder Name</label>
                        <input type="text" id="createName" class="form-control" placeholder="New Folder">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="createSubmit" type="button" class="btn btn-primary">Create</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rename Modal --}}
    <div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="renameName" class="form-label">New Name</label>
                        <input type="text" id="renameName" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="renameSubmit" type="button" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteText" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="deleteSubmit" type="button" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>


    {{-- jQuery --}}
    <script src="{{ asset('assets/jquery-3.7.1/jquery.min.js') }}"></script>

    {{-- Bootstrap 5 JS --}}
    <script src="{{ asset('assets/bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}"></script>


    <script>
        $(function() {
            const $tree = $('#tree');
            const $subfolders = $('#subfolders');
            const $files = $('#files');
            const $rightPanel = $('#right-panel');
            const $selectedInfo = $('#selectedInfo');
            const $btnCreate = $('#btnCreate');
            const $btnRename = $('#btnRename');
            const $btnDelete = $('#btnDelete');
            const createModal = new bootstrap.Modal(document.getElementById('createModal'));
            const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

            const idToNode = {};
            let selectedNode = null;

            function computeDisplayPath(node) {
                if (!node || node.id === 0) return 'root';
                const parts = [];
                let cur = node;
                while (cur && cur.id !== 0) {
                    parts.unshift(cur.name);
                    const parentId = cur.parent_id ?? null;
                    cur = parentId ? idToNode[parentId] : {
                        id: 0,
                        name: 'root',
                        parent_id: null
                    };
                }
                return parts.join('/') || 'root';
            }

            function buildTree(node, expanded = false) {
                const key = node.id;
                idToNode[key] = node;
                const isRoot = node.id === 0;
                const icon = isRoot ? '<i class="bi bi-hdd"></i>' : '<i class="bi bi-folder"></i>';
                const $li = $('<li class="my-1"></li>');
                
                // Show toggle if child_count > 0 or children already present
                const hasChildren = (node.child_count && node.child_count > 0) || (node.children && node.children.length);
                const $toggle = hasChildren ? $('<span class="me-1" role="button" aria-label="toggle"></span>')
                    .html(expanded ? '<i class="bi bi-caret-down-fill"></i>' :
                        '<i class="bi bi-caret-right-fill"></i>') : $('<span class="me-1"></span>');
                const $label = $('<span class="folder-item"></span>').html(`${icon} ${node.name || '(root)'}`).data(
                    'id', key);
                $label.on('click', () => onFolderClick(key));
                $li.append($toggle).append($label);
                
                if (hasChildren) {
                    const $ul = $('<ul></ul>');
                    if (node.children && node.children.length) {
                        node.children.forEach(child => $ul.append(buildTree(child, false)));
                    }
                    if (!expanded) {
                        $ul.hide();
                    }
                    
                    $toggle.on('click', (e) => {
                        e.stopPropagation();
                        const isOpen = $ul.is(':visible');
                        if (isOpen) {
                            $ul.slideUp(100);
                            $toggle.html('<i class="bi bi-caret-right-fill"></i>');
                        } else {
                            // Lazy-load children on first expand if not loaded yet
                            if (!$ul.children().length) {
                                const nodeRef = idToNode[key];
                                // Show loading row inside the tree while fetching
                                const $loading = $(
                                    '<li class="text-muted"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...</li>'
                                    );
                                $ul.empty().append($loading);
                                fetch(`/api/folders/${nodeRef.id}/children`).then(async (res) => {
                                    if (!res.ok) throw new Error('Failed');
                                    const children = await res.json();
                                    nodeRef.children = children;
                                    $ul.empty();

                                    children.forEach(child => {
                                        idToNode[child.id] = child;
                                        $ul.append(buildTree(child, false))
                                    });
                                    
                                    $ul.slideDown(100);
                                    $toggle.html('<i class="bi bi-caret-down-fill"></i>');
                                }).catch(() => {
                                    // If failed, show a small error entry
                                    $ul.empty().append(
                                        '<li class="text-danger">Failed to load</li>');
                                    $ul.slideDown(100);
                                    $toggle.html('<i class="bi bi-caret-down-fill"></i>');
                                });
                            } else {
                                $ul.slideDown(100);
                                $toggle.html('<i class="bi bi-caret-down-fill"></i>');
                            }
                        }
                    });

                    $li.append($ul);
                }
                return $li;
            }

            function renderTree(root) {
                const $ul = $('<ul></ul>').append(buildTree(root, true));
                $tree.empty().append($ul);

                // Auto-expand and select root if it has children
                const nodeRef = idToNode[root.id];
                const $rootLi = $tree.find('> ul > li');
                const $toggle = $rootLi.find('span[role="button"]').first();
                const $childUl = $rootLi.find('> ul');
                if (nodeRef && nodeRef.child_count > 0 && $toggle.length) {
                    if (!$childUl.children().length) {
                        fetch(`/api/folders/${nodeRef.id}/children`).then(async (res) => {
                            if (!res.ok) throw new Error('Failed');
                            const children = await res.json();
                            nodeRef.children = children;
                            $childUl.empty();
                            children.forEach(child => {
                                idToNode[child.id] = child;
                                $childUl.append(buildTree(child, false))
                            });
                            $childUl.show();
                            $toggle.html('<i class="bi bi-caret-down-fill"></i>');
                        }).catch(() => {
                            $childUl.html('<li class="text-danger">Failed to load</li>').show();
                            $toggle.html('<i class="bi bi-caret-down-fill"></i>');
                        });
                    } else {
                        $childUl.show();
                        $toggle.html('<i class="bi bi-caret-down-fill"></i>');
                    }
                }
            }

            // Refresh only one node in the left tree without re-rendering the whole root
            function refreshTreeNode(nodeId) {
                const node = idToNode[nodeId];
                if (!node) return;
                const $label = $tree.find('span.folder-item').filter(function() {
                    return $(this).data('id') === nodeId;
                }).first();
                if ($label.length === 0) return;
                const $li = $label.closest('li');
                const expanded = true; // keep the selected node expanded after refresh
                const $newLi = buildTree(node, expanded);
                $li.replaceWith($newLi);
            }

            async function onFolderClick(idKey) {
                const node = idToNode[idKey];
                if (!node) return;
                selectedNode = node;
                $selectedInfo.text(`Selected: ${node.name} (${computeDisplayPath(node)})`);
                $btnCreate.prop('disabled', false);
                const isRoot = node.id === 0;
                $btnRename.prop('disabled', isRoot);
                $btnDelete.prop('disabled', isRoot);
                // Show loading while fetching right panel data
                showLoadingRightPanel();
                // Ensure subfolders are loaded even if the tree node wasn't expanded
                let children = node.children || [];
                if ((!children || children.length === 0) && (node.child_count && node.child_count > 0)) {
                    try {
                        const resChildren = await fetch(`/api/folders/${node.id || 0}/children`);
                        if (resChildren.ok) {
                            children = await resChildren.json();
                            node.children = children;
                            children.forEach(ch => {
                                idToNode[ch.id] = ch;
                            });
                        }
                    } catch (_) {
                        // ignore fetch errors; will render empty state
                    }
                }
                renderSubfolders(children);
                // Fetch files for selected folder
                const folderId = node.id || 0;
                try {
                    const res = await fetch(`/api/folders/${folderId}/files`);
                    if (res.ok) {
                        const items = await res.json();
                        renderFiles(items);
                    } else {
                        renderFiles([]);
                    }
                } catch (_) {
                    renderFiles([]);
                }
            }

            function renderSubfolders(children) {
                $subfolders.empty();
                if (!children || children.length === 0) {
                    $subfolders.html('<div class="text-muted">No subfolders</div>');
                    return;
                }
                children.forEach(child => {
                    const $col = $('<div class="col-6 col-md-4 col-lg-3"></div>');
                    const $card = $(`
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center gap-2">
                                <i class="bi bi-folder"></i>
                                <span>${child.name}</span>
                            </div>
                        </div>
                    `);
                    $col.append($card);
                    $subfolders.append($col);
                });
            }

            function renderFiles(items) {
                $files.empty();
                if (!items || items.length === 0) {
                    $files.html('<div class="text-muted">No files</div>');
                    return;
                }
                items.forEach(it => {
                    const $col = $('<div class="col-6 col-md-4 col-lg-3"></div>');
                    const $card = $(`
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark"></i>
                                <span class="flex-grow-1">${it.name}</span>
                            </div>
                        </div>
                    `);
                    $col.append($card);
                    $files.append($col);
                });
            }

            function showLoadingRightPanel() {
                setRightPanelVisible(true);
                $subfolders.html(
                    '<div class="text-muted"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading subfolders...</div>'
                    );
                $files.html(
                    '<div class="text-muted"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading files...</div>'
                    );
            }

            function setRightPanelVisible(visible) {
                const $headersAndSeparator = $rightPanel.find('h2, hr');
                if (visible) {
                    $headersAndSeparator.show();
                    $subfolders.show();
                    $files.show();
                } else {
                    $headersAndSeparator.hide();
                    $subfolders.hide();
                    $files.hide();
                }
            }

            // Create
            $btnCreate.on('click', () => {
                if (!selectedNode) return;
                $('#createName').val('');
                createModal.show();
            });
            $('#createSubmit').on('click', async function() {
                const name = $('#createName').val().trim();
                if (!name || !selectedNode) return;
                const parentId = selectedNode.id === 0 ? null : selectedNode.id;
                const res = await fetch('/api/folders', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        parent_id: parentId
                    })
                });
                if (!res.ok) {
                    alert('Failed to create folder');
                    return;
                }
                createModal.hide();
                // After create, refresh its children
                if (selectedNode) {
                    const resChildren = await fetch(`/api/folders/${parentId || 0}/children`);
                    if (resChildren.ok) {
                        const children = await resChildren.json();
                        selectedNode.children = children;
                        selectedNode.child_count = children.length;
                        // Refresh only the selected node in the tree
                        refreshTreeNode(selectedNode.id || 0);
                        // Also refresh the right panel to show new subfolder list
                        onFolderClick(selectedNode.id || 0);
                    }
                }
            });

            // Rename
            $btnRename.on('click', () => {
                if (!selectedNode || selectedNode.id === 0) return;
                $('#renameName').val(selectedNode.name);
                renameModal.show();
            });
            $('#renameSubmit').on('click', async function() {
                const newName = $('#renameName').val().trim();
                if (!newName || !selectedNode) return;
                const id = selectedNode.id;
                const res = await fetch(`/api/folders/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: newName
                    })
                });
                if (!res.ok) {
                    alert('Failed to rename folder');
                    return;
                }
                renameModal.hide();
                // Update local node and refresh only this node in the tree
                selectedNode.name = newName;
                refreshTreeNode(selectedNode.id);
                // Keep selection and update info
                $selectedInfo.text(`Selected: ${selectedNode.name} (${computeDisplayPath(selectedNode)})`);
            });

            // Delete
            $btnDelete.on('click', () => {
                if (!selectedNode || selectedNode.id === 0) return;
                $('#deleteText').text(`Delete folder "${selectedNode.name}" and its subfolders?`);
                deleteModal.show();
            });
            $('#deleteSubmit').on('click', async function() {
                const id = selectedNode.id;
                if (!id) {
                    alert('Folder not found');
                    return;
                }
                const res = await fetch(`/api/folders/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (!res.ok) {
                    alert('Failed to delete folder');
                    return;
                }
                deleteModal.hide();
                // After delete, refresh the parent node and select it
                const parentId = idToNode[id]?.parent_id ?? 0;
                try {
                    const resChildren = await fetch(`/api/folders/${parentId || 0}/children`);
                    if (resChildren.ok) {
                        const children = await resChildren.json();
                        const parentNode = idToNode[parentId] || { id: 0, name: 'root', parent_id: null };
                        parentNode.children = children;
                        parentNode.child_count = children.length;
                        refreshTreeNode(parentNode.id);
                        // Select parent and refresh right panel
                        await onFolderClick(parentNode.id);
                    } else {
                        // If refresh failed, clear selection and right panel
                        selectedNode = null;
                        $selectedInfo.text('No folder selected');
                        $btnCreate.prop('disabled', true);
                        $btnRename.prop('disabled', true);
                        $btnDelete.prop('disabled', true);
                        setRightPanelVisible(false);
                    }
                } catch (_) {
                    selectedNode = null;
                    $selectedInfo.text('No folder selected');
                    $btnCreate.prop('disabled', true);
                    $btnRename.prop('disabled', true);
                    $btnDelete.prop('disabled', true);
                    setRightPanelVisible(false);
                }
            });

            // Keep right panel completely empty on page load
            setRightPanelVisible(false);
            // Initial render: fetch root children and build virtual root
            (async () => {
                try {
                    const res = await fetch('/api/folders/0/children');
                    if (!res.ok) throw new Error('failed');
                    const children = await res.json();
                    const rootNode = {
                        id: 0,
                        name: 'root',
                        parent_id: null,
                        children,
                        child_count: children.length
                    };
                    renderTree(rootNode);
                } catch (_) {
                    // Fallback: render an empty virtual root (lazy-load on expand)
                    renderTree({ id: 0, name: 'root', parent_id: null, children: [], child_count: 0 });
                }
            })();
        });
    </script>

</body>

</html>

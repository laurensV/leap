<!-- TODO: add https://github.com/drvic10k/bootstrap-sortable -->
<div class="table-searchable">
    <div class="input-group"> <span class="input-group-addon">Search</span>
        <input autofocus type="text" class="form-control search-table" placeholder="search for plugins..">
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Info</th>
                    <th colspan="3">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($plugins as $plugin) {
                    if (!$plugin['status']) {
                        $form = '<form action="'. BASE_URL . 'admin/plugins/enable" method="post">';
                        $form .= '<input type="hidden" value='.$plugin['pid'].' name="pid" />';
                        $form .= "<button name='pid' type='submit' data-confirm='Are you sure you want to enable ".$plugin['name']."?'class='btn btn-success btn-outline btn-sm' value='" . $plugin['pid'] . "'>Enable</button>";
                        $form .= '</form>';
                    } else {
                        $form = '<form action="'. BASE_URL . 'admin/plugins/disable" method="post">';
                        $form .= '<input type="hidden" value='.$plugin['pid'].' name="pid" />';
                        $form .= "<button type='submit' data-confirm='Are you sure you want to disable ".$plugin['name']."?' class='btn btn-danger btn-outline btn-sm' value='" . $plugin['pid'] . "'>Disable</button>";
                        $form .= '</form>';
                    }
                    if(!empty($plugin['dependencies'])){
                        $plugin['dependencies'] = "dependencies: " . $plugin['dependencies'];
                    }
                    if(!empty($plugin['source'])){
                        $plugin['source'] = "<br>source: " . $plugin['source'];
                    }
                    echo '<tr><td class="searchable">' . $plugin['name'] . ' <span class="small text-muted">(' . $plugin['pid'] . ')</span></td><td class="searchable">' . $plugin['description'] . '</td><td>' . $plugin['dependencies'] . $plugin['source'] . '</td><td>' . $form . '</td></tr>';
                }
                ?>
                <tr class="no-results" style="display: none">
                    <td colspan="10">No results</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
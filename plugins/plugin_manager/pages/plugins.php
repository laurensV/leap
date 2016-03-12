<h3>Plugins</h3>
<!-- TODO: add https://github.com/drvic10k/bootstrap-sortable -->
<div class="modal fade bs-example-modal-lg in" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-body">Loading..</div>
        </div>
    </div>
</div>
<div class="table-searchable">
    <div class="input-group"> <span class="input-group-addon">Search</span>
        <input type="text" class="form-control search-table" placeholder="search for plugins..">
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
                        $link = "<button class='btn btn-success btn-outline btn-sm' data-toggle='modal' data-remote='false' data-target='.bs-example-modal-lg' pid='" . $plugin['pid'] . "'  href='" . BASE_URL . "/admin/plugins/enable .result'>enable</button>";
                    } else {
                        $link = "<button class='btn btn-danger btn-outline btn-sm' data-toggle='modal' data-remote='false' data-target='.bs-example-modal-lg' pid='" . $plugin['pid'] . "'  href='" . BASE_URL . "/admin/plugins/disable .result'>disable</button>";
                    }
                    if(!empty($plugin['dependencies'])){
                        $plugin['dependencies'] = "dependencies: " . $plugin['dependencies'];
                    }
                    if(!empty($plugin['source'])){
                        $plugin['source'] = "<br>source: " . $plugin['source'];
                    }
                    echo '<tr><td class="searchable">' . $plugin['name'] . ' <span class="small text-muted">(' . $plugin['pid'] . ')</span></td><td class="searchable">' . $plugin['description'] . '</td><td>' . $plugin['dependencies'] . $plugin['source'] . '</td><td>' . $link . '</td></tr>';
                }
                ?>
                <tr class="no-results" style="display: none">
                    <td colspan="10">No results</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
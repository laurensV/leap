<div class='result'>
<?php 
if(isset($success_message)){
    ?>
    <div class="alert alert-success" role="alert">
      <?php echo $success_message; ?>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    </div>
<?php
} else if(isset($error_message)){
    ?>
    <div class="alert alert-danger" role="alert">
      <?php echo $error_message; ?>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    </div>
<?php 
} else if (isset($dependent_plugins)) {
    ?>
    <div class='modal-body'><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    <?php
    echo "<b>" . $dependent_plugins[0] . "</b> is a (indirect or direct) dependency for <b>" . implode(", ", array_slice($dependent_plugins, 1)) . "</b>.<br>The dependent plugins also have to be disabled, continue?";
    ?>
    <form action="<?php echo BASE_URL . "/admin/plugins/mdisable"; ?>" method="post">
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependent_plugins)); ?>" />
        <button type="submit" name="action" class="btn btn-danger" value="Disable">Disable</button>
        <button type="button" class="btn btn-default" data-dismiss="modal" aria-label="Close">Cancel</button>
    </form>
    </div>
<?php
} else if (isset($dependencies)) {
    ?>
    <div class='modal-body'><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    <?php
    echo "<b>" . $dependencies[0] . "</b> is dependent on <b>" . implode(", ", array_slice($dependencies, 1)) . "</b>.<br>Dependencies also have to be disabled, continue?";
    ?>
    <form action="<?php echo BASE_URL . "/admin/plugins/mdisable"; ?>" method="post">
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependencies)); ?>" />
        <button type="submit" name="action" class="btn btn-success" value="Enable">Enable</button>
        <button type="button" class="btn btn-default" data-dismiss="modal" aria-label="Close">Cancel</button>
    </form>
    </div>
<?php 
} else {
    header("Location: " . BASE_URL . "/admin/plugins");
}
?>
</div>
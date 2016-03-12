<?php 
if(isset($success_message)){
    ?>
    <div class="alert alert-success" role="alert">
      <?php echo $success_message; ?>
    </div>
<?php
} else if(isset($error_message)){
    ?>
    <div class="alert alert-danger" role="alert">
      <?php echo $error_message; ?>
    </div>
<?php 
} else if (isset($dependent_plugins)) {
    echo "<b>" . $dependent_plugins[0] . "</b> is a (indirect or direct) dependency for <b>" . implode(", ", array_slice($dependent_plugins, 1)) . "</b>.<br>The dependent plugins also have to be disabled, continue?";
    ?>
    <form action="<?php echo BASE_URL . "/admin/plugins/disable"; ?>" method="post">
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependent_plugins)); ?>" />
        <button type="submit" name="action" class="btn btn-danger" value="Disable">Disable</button>
        <button type="submit" name="action" class="btn btn-default" value="Cancel">Cancel</button>
    </form>
<?php
} else if (isset($dependencies)) {
    echo "<b>" . $dependencies[0] . "</b> is dependent on <b>" . implode(", ", array_slice($dependencies, 1)) . "</b>.<br>Dependencies also have to be disabled, continue?";
    ?>
    <form action="<?php echo BASE_URL . "/admin/plugins/disable"; ?>" method="post">
        <input type='hidden' name='plugins' id='plugins' value="<?php echo htmlentities(serialize($dependencies)); ?>" />
        <button type="submit" name="action" class="btn btn-success" value="Enable">Enable</button>
        <button type="submit" name="action" class="btn btn-default" value="Cancel">Cancel</button>
    </form>
<?php 
} else {
    header("Location: " . BASE_URL . "/admin/plugins");
}
if(isset($info_message)){
    ?>
    <div class="alert alert-info" role="alert">
      <?php echo $info_message; ?>
    </div>
<?php
}
if(isset($success_message) || isset($error_message)) {
    echo "<a href='" . BASE_URL . "/admin/plugins'><i class='glyphicon glyphicon-chevron-left'></i> Back to plugin list</a>";
}
?>
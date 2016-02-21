<div id="wrapper">
    <!-- Navigation -->
    <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <?php echo l("Admin Section", "admin", array("class" => "navbar-brand")); ?>
        </div>
        <!-- /.navbar-header -->

        <div class="navbar-default sidebar" role="navigation">
            <div class="sidebar-nav navbar-collapse">
                <ul class="nav" id="side-menu">
                	<?php
    	            	if(isset($links)){
    						foreach($links as $name => $link){
    							switch (strtolower($name)) {
    								case 'dashboard':
    									$name = '<span class="glyphicon glyphicon-dashboard" aria-hidden="true"></span> '.$name;
    									break;
    								case 'plugins':
    									$name = '<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> '.$name;
    									break;
    								default:
    									$name = '<span class="glyphicon glyphicon-menu-right" aria-hidden="true"></span> '.$name;
    									break;
    							}
    							echo "<li>" . l($name, $link) . "</li>";
    						}
    					}
                	?>
                    <li>
                        <a href="#"><span class="glyphicon glyphicon-th" aria-hidden="true"></span> Test<span class="fa arrow"></span></a>
                        <ul class="nav nav-second-level">
                            <li>
                                <a href="#">Test</a>
                            </li>
                            <li>
                                <a href="#">Test</a>
                            </li>
                        </ul>
                        <!-- /.nav-second-level -->
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div id="page-wrapper">
        <?php echo $page; ?>
    </div>
</div>

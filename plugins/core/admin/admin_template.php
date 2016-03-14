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
    						foreach($links as $link){
    							switch (strtolower($link['link'])) {
    								case 'admin/dashboard':
    									$link['name'] = '<span class="glyphicon glyphicon-dashboard" aria-hidden="true"></span> '.$link['name'];
    									break;
    								case 'admin/plugins':
    									$link['name'] = '<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> '.$link['name'];
    									break;
    								default:
    									$link['name'] = '<span class="glyphicon glyphicon-menu-right" aria-hidden="true"></span> '.$link['name'];
    									break;
    							}
    							echo "<li>" . l($link['name'], $link['link'], array("title"=>$link['description'])) . "</li>";
    						}
    					}
                	?>
                    <li>
                        <a href="#"><span class="glyphicon glyphicon-th" aria-hidden="true"></span> Test<span class="glyphicon glyphicon-menu-left dynamic"></span></a>
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
        <h3><?php echo $title ?></h3>
        <?php 
        echo $messages;
        echo $page; 
        ?>
    </div>
</div>

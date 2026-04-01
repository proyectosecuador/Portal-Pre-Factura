<?php
include('../../Conexion/conexion_mysqli.php');
include('../../Model/Model_gb_global.php');
include('../../control_session.php');
include('../menu.php');

?>
<!DOCTYPE html>
<html lang="es">
<?php
// Datos para headerh.php
$dato1 = 13;
$dato2 = 70;
$dato3 = 1;
include 'headerh.php';
?>

<style>
.powerbi-container {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* relación 16:9 */
    height: 0;
    overflow: hidden;
    background: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

.powerbi-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}


.nav-tabs.bar_tabs {
    border-bottom: 2px solid #e6e9ed;
}

.nav-tabs.bar_tabs > li {
    margin-bottom: -2px;
}

.nav-tabs.bar_tabs > li > a {
    background: #f5f7fa;
    border: 1px solid #e6e9ed;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    padding: 12px 20px;
    margin-right: 3px;
    color: #2c3e50;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-tabs.bar_tabs > li > a:hover {
    background: #e9edf2;
    border-color: #d0d5db;
    color: #1a2632;
}

.nav-tabs.bar_tabs > li.active > a {
    background: #ffffff !important;
    border: 2px solid #009A3F !important;
    border-bottom: 2px solid transparent !important;
    color: #009A3F !important;
    font-weight: 600;
    position: relative;
}

.nav-tabs.bar_tabs > li.active > a:after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background: #009A3F;
}

.nav-tabs.bar_tabs > li.active > a i {
    color: #009A3F;
}

.nav-tabs.bar_tabs > li > a i {
    margin-right: 8px;
    color: #6c7a8d;
    font-size: 16px;
}

.nav-tabs.bar_tabs > li.active > a i {
    color: #009A3F;
}


.nav-tabs.bar_tabs > li:nth-child(1) > a:hover {
    border-top: 2px solid #F39200;
}

.nav-tabs.bar_tabs > li:nth-child(2) > a:hover {
    border-top: 2px solid #009A3F;
}

.nav-tabs.bar_tabs > li:nth-child(3) > a:hover {
    border-top: 2px solid #6d7071;
}

.tab-content {
    background: #ffffff;
    border: 1px solid #e6e9ed;
    border-top: none;
    padding: 20px;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
</style>

<!-- page content -->
<div class="right_col" role="main">
    <div class="">
        <div class="clearfix"></div>
        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="x_panel">
                    
                    <div class="x_content">
                        <!-- Pestañas mejoradas -->
                        <div class="" role="tabpanel" data-example-id="togglable-tabs">
                            <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">
                                
                                <li role="presentation" class="">
                                    <a href="#tab_content1" id="home-tab" role="tab" data-toggle="tab" aria-expanded="false">
                                        <i class="fa fa-dashboard"></i> Dashboard IT
                                    </a>
                                </li>
                                
                                <li role="presentation" class="active">
                                    <a href="#tab_content2" role="tab" id="profile-tab" data-toggle="tab" aria-expanded="true">
                                        <i class="fa fa-clock-o"></i> SLA - MDA
                                    </a>
                                </li>
                                
                                <li role="presentation" class="">
                                    <a href="#tab_content3" role="tab" id="profile-tab2" data-toggle="tab" aria-expanded="false">
                                        <i class="fa fa-line-chart"></i> Innovación y Automatización 
                                    </a>
                                </li>
                            </ul>
                            
                            <div id="myTabContent" class="tab-content">
                                
                                <div role="tabpanel" class="tab-pane fade" id="tab_content1" aria-labelledby="home-tab">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card-box">
                                                <div class="powerbi-container">
                                                    <iframe 
                                                        src="https://app.powerbi.com/view?r=eyJrIjoiZDQ3Y2UzOWUtMGFjNS00NzRkLWJhOGUtNTk2NTgxYjYxOTdkIiwidCI6IjMyZTAxNzYzLTQxZTItNDA5My1hZWQ2LTVhZjFmOWMzNzk2NSJ9"
                                                        allowfullscreen>
                                                    </iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div role="tabpanel" class="tab-pane fade active in" id="tab_content2" aria-labelledby="profile-tab">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card-box">
                                                <div class="powerbi-container">
                                                    <iframe 
                                                        src="https://app.powerbi.com/view?r=eyJrIjoiYzU0MzZiZGQtNDcxOC00MGFhLTk1OGEtZTkyMDUxZWQxODQ3IiwidCI6IjMyZTAxNzYzLTQxZTItNDA5My1hZWQ2LTVhZjFmOWMzNzk2NSJ9"
                                                        allowfullscreen>
                                                    </iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div role="tabpanel" class="tab-pane fade" id="tab_content3" aria-labelledby="profile-tab2">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card-box">
                                                <div class="powerbi-container">
                                                    <iframe 
                                                        src="https://app.powerbi.com/view?r=eyJrIjoiNzBhNzM2YzktY2VhNy00MTJiLWE0ZGMtN2YzYjI2Y2Y4MzU1IiwidCI6IjMyZTAxNzYzLTQxZTItNDA5My1hZWQ2LTVhZjFmOWMzNzk2NSJ9"
                                                        allowfullscreen>
                                                    </iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer2h.php'; ?>

<script>
    document.title = "Dashboard Indicadores - Mesa de Ayuda";
    
    $(document).ready(function() {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            $(window).resize();
        });

        $('#myTab a[href="#tab_content2"]').tab('show');
    });
</script>

</body>
</html>
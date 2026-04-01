<?php
function sistema_menu($modulo, $interfaz, $origen) {    
    $conn = conexionSQL();
    $Global = new ModelGlobal();
    

    // SQL original para traer los módulos 8 y 9
    // --- INICIO DE MENÚ QUEMADO (AGRUPADOR) ---
    ?>
    <div class="menu_section">
        <ul class="nav side-menu">
          <?php

          
if(isset( $_SESSION["id_user"])) { } else {
	exit(1);
}
$iduser= $_SESSION["id_user"];
    //consultamos los apps que tiene acceso, similar a apps.php

$sql = "SELECT ca.* from it.conf_user_app cua 
inner join it.conf_apps ca ON 
ca.id_app  = cua.id_app 
where id_user=?";
$params = array($iduser);

$stmt = sqlsrv_query($conn, $sql, $params);

	while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $modulos = $row['modulos_menu'];
        $url = $row['url'];;
// Dividimos la cadena en el signo =
$partes = explode('=', $url);
?>
				   <li>
                <a>
                    <i class="<?php echo $row['icono'] ?>"></i><?php echo $row['nombre'] ?>
                    <span class="fa fa-chevron-down"></span>
                </a>
                <!-- style="display: block;" -->
                <ul class="nav child_menu" > <?php 

                
    $sql = "SELECT * FROM gb_modulo WHERE gb_estatus='1' and gb_id_modulo in($modulos) ORDER BY gb_id_modulo ASC";
    $resultado = sqlsrv_query($conn, $sql);


                    while ($fila = sqlsrv_fetch_array($resultado, SQLSRV_FETCH_ASSOC)) {
                        /** PERFIL ADMINISTRADOR **/
                        $validacion = $Global->modulo_permitido($fila['gb_id_modulo'], $_SESSION["gb_perfil"]) == 1;

                        if($validacion) {
                    ?>
                        <li style="border-left: 2px solid #1ABB9C; margin-bottom: 5px;COLOR:ORANGE">
                            <a style="font-weight: bold;">
                                <i class="<?php echo $fila['gb_icono_modulo']; ?>"></i> 
                                <?php echo $fila['gb_nombre_modulo']; ?>
                                <span class="fa fa-chevron-down"></span>
                            </a>
                            
                            <ul class="nav child_menu">
                                <?php
                                $sql_menu = "SELECT * FROM gb_menu WHERE gb_id_modulo ='".$fila['gb_id_modulo']."' AND gb_estatus='1' order by gb_id_menu asc";
                                $resultado_menu = sqlsrv_query($conn, $sql_menu);

                                while ($fila_menu = sqlsrv_fetch_array($resultado_menu, SQLSRV_FETCH_ASSOC)) {
                                    $ext = ($origen == 0) ? $fila_menu['gb_raiz'].'/' : '';
                                    
                                    // Validación de página actual para el color naranja
                                    $estilo_active = ($interfaz == $fila_menu['gb_id_menu']) ? 'class="current-page" style="background-color:orange"' : '';
                                    ?>
                                    <li <?php echo $estilo_active; ?>>
                                        <a href="../../../<?php echo $partes[0]; ?>=<?php echo $ext.$fila_menu['gb_archivo']; ?>">
                                            <?php echo $fila_menu['gb_nombre_menu']; ?>
                                        </a>
                                    </li>
                                    <?php
                                } // Fin while menu items
                                ?>
                            </ul>
                        </li>
                    <?php 
                        } // Fin validación
                    } // Fin while módulos
                    ?>

                </ul> 
            </li>
<?php 
}
?>


        </ul>
    </div>
    <?php
    // --- FIN DE MENÚ QUEMADO ---
}
?>
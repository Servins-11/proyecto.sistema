<?php
include 'conexion.php';

// Procesar alta de nuevo producto
$mensaje_exito = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'agregar_producto') {
        $codigo = trim($_POST['codigo_barras']);
        $nombre = trim($_POST['nombre_producto']);
        $unidad = trim($_POST['unidad_medida']);
        $precio = floatval($_POST['precio_unitario']);
        $stock = intval($_POST['stock_actual']);
        $min = intval($_POST['stock_minimo']);

        if (!empty($nombre) && !empty($unidad) && $precio > 0 && $stock >= 0 && $min >= 0) {
            $stmt = $pdo->prepare("INSERT INTO producto (codigo_barras, nombre_producto, unidad_medida, precio_unitario, stock_actual, stock_minimo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nombre, $unidad, $precio, $stock, $min]);
            $mensaje_exito = "¡Material agregado correctamente con su código de barras!";
        }
    } 
    // Procesar eliminación de producto
    elseif ($_POST['accion'] === 'eliminar_producto') {
        $id_producto = intval($_POST['id_producto']);
        if ($id_producto > 0) {
            $stmtDel = $pdo->prepare("DELETE FROM producto WHERE id_producto = ?");
            $stmtDel->execute([$id_producto]);
            $mensaje_exito = "¡Material eliminado del inventario correctamente!";
        }
    }
}

// Obtener inventario actualizado de MySQL
$stmtProd = $pdo->query("SELECT * FROM producto");
$productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

// Obtener clientes de MySQL
$stmtCli = $pdo->query("SELECT * FROM cliente");
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

// Calcular capital invertido total
$capitalInvertido = 0;
foreach($productos as $p) {
    $capitalInvertido += ($p['precio_unitario'] * $p['stock_actual']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materiales de Construcción Virgen del Paso</title>
    <style>
        :root {
            --primary: #1e3a8a;
            --secondary: #d97706;
            --danger: #dc2626;
            --bg: #f1f5f9;
            --text: #1e293b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--bg); color: var(--text); display: flex; justify-content: center; align-items: center; min-height: 100vh; }

        /* PANTALLA DE ACCESO (LOGIN) */
        #login-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(30, 58, 138, 0.85); display: flex; justify-content: center; align-items: center; z-index: 2000;
        }
        .login-card {
            background: white; padding: 35px; border-radius: 12px; width: 350px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .login-card h2 { margin-bottom: 20px; color: var(--primary); text-align: center; font-size: 1.3rem; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; font-weight: 600; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; }
        .btn { background: var(--primary); color: white; border: none; padding: 10px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.95rem; transition: background 0.2s; }
        .btn:hover { background: #1d4ed8; }

        /* CONTENEDOR PRINCIPAL */
        #app-container {
            width: 95%; max-width: 1150px; height: 88vh; background: white; border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; overflow: hidden;
        }

        /* MENÚ LATERAL */
        aside { width: 260px; background: var(--primary); color: white; display: flex; flex-direction: column; }
        .aside-header { padding: 25px 20px; font-size: 1rem; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; background: #1e3a8add; }
        .menu { list-style: none; padding: 15px 0; flex-grow: 1; }
        .menu li { padding: 14px 25px; cursor: pointer; transition: background 0.2s; font-size: 0.95rem; }
        .menu li:hover, .menu li.active { background: rgba(255,255,255,0.15); border-left: 4px solid var(--secondary); }
        
        /* ZONA DE TRABAJO */
        main { flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; background: #ffffff; }
        header { background: #ffffff; padding: 20px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .content { padding: 30px; flex-grow: 1; }
        
        .card { background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.9rem; }
        table th { background: #f8fafc; font-weight: 600; color: #475569; }
        .alert { background: #fee2e2; color: var(--danger); font-weight: bold; }
        .hidden { display: none !important; }
        .logout-btn { background: #fee2e2; color: var(--danger); border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: bold; }
        .delete-btn { background: #fee2e2; color: var(--danger); border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; }
        .delete-btn:hover { background: #fecaca; }
        
        .total-inversion-box {
            background: #eff6ff; border: 1px solid #bfdbfe; padding: 15px 20px; border-radius: 6px;
            margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;
        }
        .total-inversion-box h4 { font-size: 0.95rem; color: #1e40af; }
        .total-inversion-box span { font-size: 1.2rem; font-weight: bold; color: #1e3a8a; }

        .ticket-box { background: #fff; padding: 20px; border: 1px dashed #94a3b8; border-radius: 6px; margin-top: 20px; max-width: 450px; }
        .ticket-header { text-align: center; border-bottom: 1px dashed #cbd5e1; padding-bottom: 10px; margin-bottom: 10px; }
        .ticket-header h4 { font-size: 1.1rem; color: var(--primary); }
        .ticket-header p { font-size: 0.8rem; color: #64748b; }
        .ticket-body { font-size: 0.9rem; line-height: 1.5; }
        @media print {
            body * { visibility: hidden; }
            #ticket-resultado, #ticket-resultado * { visibility: visible; }
            #ticket-resultado { position: absolute; left: 0; top: 0; width: 100%; border: none; }
        }
    </style>
</head>
<body>

    <!-- PANTALLA DE ACCESO -->
    <div id="login-container">
        <div class="login-card">
            <h2>Virgen del Paso</h2>
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" id="user" placeholder="Ej: gerente o recepcion">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" id="pass" placeholder="Contraseña">
            </div>
            <button class="btn" onclick="iniciarSesion()">Ingresar al Sistema</button>
            <p id="error-msg" style="color:red; font-size:0.8rem; text-align:center; margin-top:12px;"></p>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <div id="app-container" class="hidden">
        <aside>
            <div class="aside-header">Materiales Virgen del Paso</div>
            <ul class="menu">
                <li class="active" onclick="cambiarSeccion('inicio', this)">Inicio</li>
                <li onclick="cambiarSeccion('clientes', this)">Clientes</li>
                <li onclick="cambiarSeccion('inventario', this)">Inventario</li>
                <li onclick="cambiarSeccion('ventas', this)">Ventas y Caja</li>
            </ul>
        </aside>

        <main>
            <header>
                <h3 id="titulo-seccion" style="font-size: 1.2rem; color: var(--primary);">Panel Principal</h3>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span id="user-info" style="font-size: 0.9rem; font-weight: 600; color: #475569;"></span>
                    <button class="logout-btn" onclick="cerrarSesion()">Cerrar Sesión</button>
                </div>
            </header>

            <div class="content">
                <!-- SECCIÓN INICIO -->
                <section id="sec-inicio" class="card">
                    <h3 style="color: var(--primary); margin-bottom: 10px;">Sistema Informático de Gestión</h3>
                    <p style="color: #64748b; line-height: 1.6;">Conectado a la base de datos MySQL local (`materiales_virgen_del_paso`). Gestión operativa para <b>Materiales de Construcción Virgen del Paso</b> (Jesús de Tavarangüé).</p>
                </section>

                <!-- SECCIÓN CLIENTES -->
                <section id="sec-clientes" class="card hidden">
                    <h3>Gestión de Clientes (MySQL)</h3>
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Cédula</th><th>Nombre y Apellido</th><th>Teléfono</th><th>Barrio / Ciudad</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $cli): ?>
                            <tr>
                                <td><?php echo $cli['id_cliente']; ?></td>
                                <td><?php echo htmlspecialchars($cli['cedula']); ?></td>
                                <td><?php echo htmlspecialchars($cli['nombre'] . ' ' . $cli['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($cli['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($cli['barrio'] . ' - ' . $cli['ciudad']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- SECCIÓN INVENTARIO -->
                <section id="sec-inventario" class="card hidden">
                    <h3>Control de Inventario y Código de Barras</h3>
                    
                    <?php if(!empty($mensaje_exito)): ?>
                        <div style="background: #dcfce7; color: #166534; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem; font-weight: 600;">
                            <?php echo $mensaje_exito; ?>
                        </div>
                    <?php endif; ?>

                    <div class="total-inversion-box">
                        <div>
                            <h4>Capital Total Invertido en Mercaderías:</h4>
                            <p style="font-size: 0.8rem; color: #64748b;">Suma total calculada desde la base de datos.</p>
                        </div>
                        <span>Gs. <?php echo number_format($capitalInvertido, 0, ',', '.'); ?></span>
                    </div>

                    <!-- Formulario para agregar productos con código de barras -->
                    <form method="POST" style="margin-top: 15px; background: #f8fafc; padding: 15px; border-radius: 6px; display: flex; gap: 10px; flex-wrap: wrap; border: 1px solid #e2e8f0;">
                        <input type="hidden" name="accion" value="agregar_producto">
                        <input type="text" name="codigo_barras" placeholder="Cód. Barras (Opcional)" style="padding: 9px; width: 150px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <input type="text" name="nombre_producto" placeholder="Nombre del material" required style="padding: 9px; flex: 2; min-width: 150px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <input type="text" name="unidad_medida" placeholder="Unidad (Bolsa, m³)" required style="padding: 9px; width: 120px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <input type="number" name="precio_unitario" placeholder="Precio (Gs.)" step="0.01" required style="padding: 9px; width: 120px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <input type="number" name="stock_actual" placeholder="Stock" required style="padding: 9px; width: 90px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <input type="number" name="stock_minimo" placeholder="Mín." required style="padding: 9px; width: 80px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <button type="submit" class="btn" style="width: auto; padding: 0 15px;">Añadir</button>
                    </form>

                    <table>
                        <thead>
                            <tr><th>Cód. Barras</th><th>Material</th><th>Unidad</th><th>Precio Unitario (Gs.)</th><th>Stock</th><th>Mín.</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($productos as $prod): 
                                $esAlerta = $prod['stock_actual'] <= $prod['stock_minimo'];
                            ?>
                            <tr class="<?php echo $esAlerta ? 'alert' : ''; ?>">
                                <td><code><?php echo htmlspecialchars($prod['codigo_barras'] ?? 'S/C'); ?></code></td>
                                <td><?php echo htmlspecialchars($prod['nombre_producto']); ?></td>
                                <td><?php echo htmlspecialchars($prod['unidad_medida']); ?></td>
                                <td><?php echo number_format($prod['precio_unitario'], 0, ',', '.'); ?></td>
                                <td><?php echo $prod['stock_actual']; ?></td>
                                <td><?php echo $prod['stock_minimo']; ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Está seguro de eliminar este material?');" style="display:inline;">
                                        <input type="hidden" name="accion" value="eliminar_producto">
                                        <input type="hidden" name="id_producto" value="<?php echo $prod['id_producto']; ?>">
                                        <button type="submit" class="delete-btn">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- SECCIÓN VENTAS -->
                <section id="sec-ventas" class="card hidden">
                    <h3>Registrar Venta / Pedido</h3>
                    <div style="margin-top: 15px; display: flex; gap: 15px; flex-wrap: wrap; background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <div style="flex: 1; min-width: 200px;">
                            <label style="font-size: 0.85rem; display: block; margin-bottom: 5px; font-weight: 600;">Cliente</label>
                            <select id="v-cliente" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                                <?php foreach($clientes as $cli): ?>
                                    <option value="<?php echo $cli['nombre'] . ' ' . $cli['apellido']; ?>"><?php echo $cli['nombre'] . ' ' . $cli['apellido']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label style="font-size: 0.85rem; display: block; margin-bottom: 5px; font-weight: 600;">Material</label>
                            <select id="v-material" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                                <?php foreach($productos as $prod): ?>
                                    <option value="<?php echo $prod['nombre_producto']; ?>" data-precio="<?php echo $prod['precio_unitario']; ?>"><?php echo $prod['nombre_producto']; ?> (<?php echo number_format($prod['precio_unitario'], 0, ',', '.'); ?> Gs.)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="width: 120px;">
                            <label style="font-size: 0.85rem; display: block; margin-bottom: 5px; font-weight: 600;">Cantidad</label>
                            <input type="number" id="v-cant" value="1" min="1" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button class="btn" style="padding: 9px 20px;" onclick="registrarVenta()">Procesar Venta</button>
                        </div>
                    </div>

                    <div id="ticket-resultado" class="ticket-box hidden">
                        <div class="ticket-header">
                            <h4>Materiales de Construcción Virgen del Paso</h4>
                            <p>Av. Cnel. José Félix Bogado - Jesús de Tavarangüé<br>Comprobante Interno de Venta</p>
                        </div>
                        <div class="ticket-body" id="ticket-detalles"></div>
                        <div style="text-align: center; margin-top: 15px;">
                            <button class="btn" style="width: auto; padding: 6px 15px; font-size: 0.85rem;" onclick="window.print()">Imprimir Ticket</button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function iniciarSesion() {
            const u = document.getElementById('user').value.trim();
            const p = document.getElementById('pass').value.trim();
            if((u === 'gerente' && p === 'admin123') || (u === 'recepcion' && p === 'ventas123')) {
                document.getElementById('login-container').classList.add('hidden');
                document.getElementById('app-container').classList.remove('hidden');
                document.getElementById('user-info').innerText = `Usuario: ${u}`;
            } else {
                document.getElementById('error-msg').innerText = 'Usuario o contraseña incorrectos';
            }
        }

        function cerrarSesion() {
            document.getElementById('app-container').classList.add('hidden');
            document.getElementById('login-container').classList.remove('hidden');
            document.getElementById('user').value = '';
            document.getElementById('pass').value = '';
            document.getElementById('error-msg').innerText = '';
        }

        function cambiarSeccion(seccion, elemento) {
            document.querySelectorAll('main section').forEach(sec => sec.classList.add('hidden'));
            document.getElementById(`sec-${seccion}`).classList.remove('hidden');
            document.querySelectorAll('.menu li').forEach(li => li.classList.remove('active'));
            elemento.classList.add('active');
            
            const titulos = { inicio: 'Panel Principal', clientes: 'Gestión de Clientes', inventario: 'Control de Inventario', ventas: 'Ventas y Caja' };
            document.getElementById('titulo-seccion').innerText = titulos[seccion];
        }

        function registrarVenta() {
            const cliente = document.getElementById('v-cliente').value;
            const selectMat = document.getElementById('v-material');
            const material = selectMat.value;
            const precio = parseFloat(selectMat.options[selectMat.selectedIndex].getAttribute('data-precio'));
            const cant = parseInt(document.getElementById('v-cant').value);
            const total = cant * precio;
            const fecha = new Date().toLocaleString();

            const ticketBox = document.getElementById('ticket-resultado');
            const detalles = document.getElementById('ticket-detalles');
            ticketBox.classList.remove('hidden');
            detalles.innerHTML = `
                <b>Fecha:</b> ${fecha}<br>
                <b>Cliente:</b> ${cliente}<br>
                --------------------------------------------------<br>
                <b>Material:</b> ${material}<br>
                <b>Cantidad:</b> ${cant}<br>
                <b>Precio Unit.:</b> Gs. ${precio.toLocaleString('es-PY')}<br>
                --------------------------------------------------<br>
                <b>Total a Cobrar: Gs. ${total.toLocaleString('es-PY')}</b><br>
                <i style="font-size: 0.8rem; color: #16a34a;">Estado: Transacción procesada</i>
            `;
        }
    </script>
</body>
</html>

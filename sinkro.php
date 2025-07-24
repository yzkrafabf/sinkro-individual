<?php
session_start();

// Inicializar datos si no existen
if (!isset($_SESSION['admin'])) {
    $_SESSION['admin'] = [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT) // Contraseña: admin123
    ];
}

if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [
        ['id' => 1, 'name' => 'Hamburguesa Clásica', 'price' => 65.00, 'image' => 'burger.png'],
        ['id' => 2, 'name' => 'Pizza Margarita', 'price' => 120.00, 'image' => 'pizza.png'],
        ['id' => 3, 'name' => 'Ensalada César', 'price' => 75.00, 'image' => 'salad.png'],
        ['id' => 4, 'name' => 'Refresco', 'price' => 25.00, 'image' => 'soda.png']
    ];
}

if (!isset($_SESSION['orders'])) {
    $_SESSION['orders'] = [];
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Procesar acciones
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_POST['username'] === $_SESSION['admin']['username'] && 
            password_verify($_POST['password'], $_SESSION['admin']['password'])) {
            $_SESSION['logged_in'] = true;
            header('Location: sinkro.php?view=admin');
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
        break;
        
    case 'logout':
        session_destroy();
        header('Location: sinkro.php');
        exit;
        break;
        
    case 'add_to_cart':
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        header('Location: sinkro.php');
        exit;
        break;
        
    case 'place_order':
        if (!empty($_SESSION['cart'])) {
            $folio = 'ORD-' . strtoupper(uniqid());
            $order = [
                'folio' => $folio,
                'status' => 'En proceso',
                'items' => [],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $total = 0;
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                foreach ($_SESSION['products'] as $product) {
                    if ($product['id'] == $product_id) {
                        $order['items'][] = [
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'quantity' => $quantity
                        ];
                        $total += $product['price'] * $quantity;
                    }
                }
            }
            
            $order['total'] = $total;
            $_SESSION['orders'][] = $order;
            $_SESSION['last_order'] = $folio;
            $_SESSION['cart'] = [];
            header('Location: sinkro.php?view=order_confirmation');
            exit;
        }
        break;
        
    case 'update_status':
        if ($_SESSION['logged_in'] ?? false) {
            $folio = $_POST['folio'];
            $new_status = $_POST['status'];
            
            foreach ($_SESSION['orders'] as &$order) {
                if ($order['folio'] === $folio) {
                    $order['status'] = $new_status;
                    break;
                }
            }
            header('Location: sinkro.php?view=admin');
            exit;
        }
        break;
}

// Determinar qué vista mostrar
$view = $_GET['view'] ?? '';

if ($view === 'admin' && ($_SESSION['logged_in'] ?? false)) {
    // Vista de administrador
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sinkro - Admin</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .logout { float: right; }
        </style>
    </head>
    <body>
        <h1>Panel de Administración <a href="sinkro.php?action=logout" class="logout">Cerrar sesión</a></h1>
        
        <h2>Pedidos</h2>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($_SESSION['orders']) as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['folio']) ?></td>
                    <td>
                        <form action="sinkro.php?action=update_status" method="post">
                            <input type="hidden" name="folio" value="<?= $order['folio'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="En proceso" <?= $order['status'] === 'En proceso' ? 'selected' : '' ?>>En proceso</option>
                                <option value="Listo para recoger" <?= $order['status'] === 'Listo para recoger' ? 'selected' : '' ?>>Listo para recoger</option>
                                <option value="Recogido" <?= $order['status'] === 'Recogido' ? 'selected' : '' ?>>Recogido</option>
                            </select>
                        </form>
                    </td>
                    <td><?= $order['created_at'] ?></td>
                    <td>$<?= number_format($order['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
} elseif ($view === 'login') {
    // Vista de login
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sinkro - Login</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; }
            .form-group { margin-bottom: 15px; }
            input { width: 100%; padding: 8px; }
            button { padding: 10px 15px; background: #4CAF50; color: white; border: none; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h1>Iniciar sesión</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form action="sinkro.php?action=login" method="post">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
    </body>
    </html>
    <?php
} elseif ($view === 'cart') {
    // Vista del carrito
    $cart_items = [];
    $total = 0;
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        foreach ($_SESSION['products'] as $product) {
            if ($product['id'] == $product_id) {
                $product['quantity'] = $quantity;
                $product['subtotal'] = $product['price'] * $quantity;
                $total += $product['subtotal'];
                $cart_items[] = $product;
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sinkro - Carrito</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            button { padding: 10px 15px; background: #4CAF50; color: white; border: none; }
        </style>
    </head>
    <body>
        <h1>Tu carrito</h1>
        
        <?php if (!empty($cart_items)): ?>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3"><strong>Total:</strong></td>
                    <td><strong>$<?= number_format($total, 2) ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <form action="sinkro.php?action=place_order" method="post">
            <button type="submit">Realizar pedido</button>
        </form>
        <?php else: ?>
        <p>Tu carrito está vacío.</p>
        <?php endif; ?>
        
        <a href="sinkro.php">Seguir comprando</a>
    </body>
    </html>
    <?php
} elseif ($view === 'order_confirmation') {
    // Vista de confirmación de pedido
    $folio = $_SESSION['last_order'] ?? '';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sinkro - Pedido Confirmado</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; text-align: center; }
            .folio { font-size: 24px; margin: 20px 0; }
            a { display: inline-block; margin: 10px; padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; }
        </style>
    </head>
    <body>
        <h1>¡Pedido realizado con éxito!</h1>
        <p>Tu folio es:</p>
        <div class="folio"><?= htmlspecialchars($folio) ?></div>
        <p>Puedes ver el estado de tu pedido en cualquier momento.</p>
        <a href="sinkro.php">Volver al menú</a>
        <a href="sinkro.php?view=check_status">Ver estado de mi pedido</a>
    </body>
    </html>
    <?php
} elseif ($view === 'check_status') {
    // Vista para verificar estado del pedido
    $order = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['folio'])) {
        $folio = $_POST['folio'];
        foreach ($_SESSION['orders'] as $o) {
            if ($o['folio'] === $folio) {
                $order = $o;
                break;
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sinkro - Estado del Pedido</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .status { font-weight: bold; }
            .not-found { color: red; }
        </style>
    </head>
    <body>
        <h1>Estado de mi pedido</h1>
        
        <form method="post">
            <p>
                <label for="folio">Ingresa tu folio:</label>
                <input type="text" id="folio" name="folio" required>
                <button type="submit">Buscar</button>
            </p>
        </form>
        
        <?php if ($order): ?>
        <div class="order-status">
            <h2>Folio: <?= htmlspecialchars($order['folio']) ?></h2>
            <p>Estado: <span class="status"><?= htmlspecialchars($order['status']) ?></span></p>
            <p>Fecha: <?= $order['created_at'] ?></p>
            
            <h3>Detalles del pedido:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3"><strong>Total:</strong></td>
                        <td><strong>$<?= number_format($order['total'], 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p class="not-found">No se encontró ningún pedido con ese folio.</p>
        <?php endif; ?>
        
        <a href="sinkro.php">Volver al menú</a>
    </body>
    </html>
    <?php
} else {
    // Vista principal (menú de productos)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sinkro - Menú</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; }
            .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
            .product { border: 1px solid #ddd; padding: 15px; border-radius: 4px; }
            .product img { max-width: 100%; height: 150px; object-fit: cover; }
            .cart-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
            .admin-link { text-align: right; margin-bottom: 20px; }
            input[type="number"] { width: 60px; }
            button { background: #4CAF50; color: white; border: none; padding: 5px 10px; }
        </style>
    </head>
    <body>
        <div class="admin-link">
            <a href="sinkro.php?view=login">Acceso administrador</a>
        </div>
        
        <h1>Menú</h1>
        
        <div class="cart-info">
            <a href="sinkro.php?view=cart">Ver carrito (<?= array_sum($_SESSION['cart']) ?>)</a>
            <a href="sinkro.php?view=check_status">Ver estado de mi pedido</a>
        </div>
        
        <div class="products">
            <?php foreach ($_SESSION['products'] as $product): ?>
            <div class="product">
                <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p>$<?= number_format($product['price'], 2) ?></p>
                <form action="sinkro.php?action=add_to_cart" method="post">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="number" name="quantity" value="1" min="1">
                    <button type="submit">Añadir al carrito</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
}
?>

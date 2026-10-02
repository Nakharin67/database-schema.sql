<?php
// เชื่อมต่อฐานข้อมูล MySQL
$host = 'localhost';
$username = 'root';
$password = 'rootroot';
$dbname = 'smart_loyalty_db';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$message = "";
$customer = null;

// จัดการการส่งข้อมูลผ่าน Form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $phone = trim($_POST['phone']);

        // ค้นหาสมาชิก
        if ($_POST['action'] == 'search') {
            $stmt = $conn->prepare("SELECT * FROM customers WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
            } else {
                $message = "❌ ไม่พบข้อมูลสมาชิกเบอร์นี้ในระบบ";
            }
            $stmt->close();
        }

        // สมัครสมาชิกใหม่
        elseif ($_POST['action'] == 'register') {
            $name = trim($_POST['name']);
            $stmt = $conn->prepare("INSERT INTO customers (phone, name, points) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $phone, $name);
            if ($stmt->execute()) {
                $message = "✨ ลงทะเบียนสมาชิกสำเร็จ!";
                // ดึงข้อมูลสมาชิกใหม่ขึ้นมาแสดง
                $customer = ['id' => $conn->insert_id, 'phone' => $phone, 'name' => $name, 'points' => 0];
            } else {
                $message = "❌ เบอร์โทรศัพท์นี้ถูกใช้งาน, มีสมาชิกในระบบแล้ว";
            }
            $stmt->close();
        }

        // บันทึกยอดซื้อและคำนวณแต้ม (ทุก 25 บาท = 1 แต้ม)
        elseif ($_POST['action'] == 'add_purchase' && isset($_POST['customer_id'])) {
            $customer_id = $_POST['customer_id'];
            floatval($amount = $_POST['amount']);
            
            if ($amount > 0) {
                $earned_points = floor($amount / 25); // คำนวณแต้มอัตโนมัติ

                // บันทึก Transaction
                $stmt = $conn->prepare("INSERT INTO transactions (customer_id, total_amount, earned_points) VALUES (?, ?, ?)");
                $stmt->bind_param("idi", $customer_id, $amount, $earned_points);
                $stmt->execute();
                $stmt->close();

                // อัปเดตแต้มรวมของลูกค้า
                $stmt = $conn->prepare("UPDATE customers SET points = points + ? WHERE id = ?");
                $stmt->bind_param("ii", $earned_points, $customer_id);
                $stmt->execute();
                $stmt->close();

                $message = "🎉 บันทึกยอดซื้อสำเร็จ! ได้รับแต้มสะสมเพิ่ม " . $earned_points . " แต้ม";

                // ดึงข้อมูลอัปเดตล่าสุด
                $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $customer = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Smart Loyalty Point System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #2c3e50; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { background: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; margin-top: 5px; }
        button:hover { background: #2980b9; }
        .alert { background: #e8f8f5; color: #16a085; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .card { background: #f9f9f9; border-left: 5px solid #3498db; padding: 15px; margin-top: 20px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Smart Loyalty Point System</h2>
    <p style="text-align: center; color: #7f8c8d;">ระบบสะสมแต้มซื้อสินค้า (รายวิชา COMP342)</p>

    <?php if(!empty($message)): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- ฟอร์มค้นหาเบอร์โทรศัพท์ -->
    <form method="POST" action="">
        <input type="hidden" name="action" value="search">
        <div class="form-group">
            <label>ค้นหาเบอร์โทรศัพท์สมาชิก:</label>
            <input type="text" name="phone" placeholder="ระบุเบอร์โทรศัพท์ 10 หลัก" required>
        </div>
        <button type="submit">ค้นหาสมาชิก</button>
    </form>

    <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">

    <!-- ฟอร์มสมัครสมาชิกใหม่ (กรณีไม่พบเบอร์) -->
    <form method="POST" action="">
        <input type="hidden" name="action" value="register">
        <h4 style="color: #e67e22;">กรณีลูกค้าใหม่ (ลงทะเบียน)</h4>
        <div class="form-group">
            <label>เบอร์โทรศัพท์:</label>
            <input type="text" name="phone" placeholder="ระบุเบอร์โทรศัพท์" required>
        </div>
        <div class="form-group">
            <label>ชื่อ-นามสกุล ลูกค้า:</label>
            <input type="text" name="name" placeholder="ระบุชื่อลูกค้า" required>
        </div>
        <button type="submit" style="background: #e67e22;">สมัครสมาชิกใหม่</button>
    </form>

    <!-- แสดงข้อมูลเมื่อพบบัญชีสมาชิก -->
    <?php if ($customer): ?>
        <div class="card">
            <h3>ข้อมูลสมาชิก</h3>
            <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
            <p><strong>แต้มสะสมคงเหลือ:</strong> <span style="color: #e74c3c; font-size: 20px;"><?php echo $customer['points']; ?></span> แต้ม</p>

            <hr style="border: 0; border-top: 1px dashed #ccc; margin: 15px 0;">

            <!-- ฟอร์มบันทึกยอดซื้อ -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_purchase">
                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                <input type="hidden" name="phone" value="<?php echo $customer['phone']; ?>">
                <div class="form-group">
                    <label>บันทึกยอดซื้อสินค้า (บาท): *(ทุก 25 บาท = 1 แต้ม)</label>
                    <input type="number" step="0.01" name="amount" placeholder="ระบุยอดเงินซื้อสินค้า" required>
                </div>
                <button type="submit" style="background: #27ae60;">บันทึกยอดซื้อและคำนวณแต้ม</button>
            </form>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
<?php
class Order
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create($orderData, $cartItems)
    {
        try {
            // Bắt đầu Transaction
            $this->db->beginTransaction();

            // 1. Chèn vào bảng orders
            $this->db->query("INSERT INTO orders (user_id, fullname, phone, address, note, total_price) 
                              VALUES (:user_id, :fullname, :phone, :address, :note, :total_price)");
            $this->db->bind(':user_id', $orderData['user_id']);
            $this->db->bind(':fullname', $orderData['fullname']);
            $this->db->bind(':phone', $orderData['phone']);
            $this->db->bind(':address', $orderData['address']);
            $this->db->bind(':note', $orderData['note']);
            $this->db->bind(':total_price', $orderData['total_price']);
            $this->db->execute();

            $orderId = $this->db->lastInsertId();

            // 2. Chèn từng món vào order_items
            foreach ($cartItems as $item) {
                $this->db->query("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (:order_id, :product_id, :quantity, :price)");
                $this->db->bind(':order_id', $orderId);
                $this->db->bind(':product_id', $item['product_id']);
                $this->db->bind(':quantity', $item['quantity']);
                $this->db->bind(':price', $item['price']);
                $this->db->execute();
            }

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            // TẠM THỜI IN LỖI RA MÀN HÌNH ĐỂ DEBUG:
            die("Lỗi đặt hàng: " . $e->getMessage());
            // return false;
        }
    }

    public function getOrdersByUserId($userId)
    {
        $this->db->query("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function getAllOrders()
    {
        $this->db->query("SELECT o.*, u.fullname as user_name 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC");
        return $this->db->resultSet();
    }

    public function getOrderDetail($orderId)
    {
        $this->db->query("SELECT o.*, u.email as user_email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = :id");
        $this->db->bind(':id', $orderId);
        $order = $this->db->single();

        if ($order) {

            $this->db->query("SELECT oi.*, p.name, p.image 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = :oid");
            $this->db->bind(':oid', $orderId);
            $order['items'] = $this->db->resultSet();
        }
        return $order;
    }

    public function updateStatus($id, $status)
    {
        $this->db->query("UPDATE orders SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}

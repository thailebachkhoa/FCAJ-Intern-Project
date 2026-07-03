Mục tiêu: Hãy giúp tôi thiết kế và xây dựng một ứng dụng Web có tên là "AI Context Bundler". Công cụ này cho phép người dùng tải lên các file/folder thông qua giao diện trực quan (UI), tự động duyệt qua cấu trúc thư mục, gộp nội dung các file lại thành một file văn bản duy nhất (.txt hoặc .md) để làm dữ liệu đầu vào (Prompt Context) cho các mô hình AI (LLM).

Yêu cầu chi tiết về các module hệ thống:

1. Giao diện Người dùng (Frontend UI):
- Vùng Drag & Drop: Cho phép kéo thả trực tiếp thư mục hoặc các file từ máy tính vào.
- File Tree View (Cây thư mục tương tác): Sau khi tải lên, hiển thị toàn bộ cấu trúc file/folder dưới dạng sơ đồ cây.
- Checkbox Selection: Mỗi file và folder có một checkbox để người dùng tùy chọn "Bao gồm" hoặc "Bỏ qua" (Ví dụ: để bỏ các thư mục nặng như node_modules, .git, dist).

2. Logic Xử lý Gộp File (Core Engine):
- Thực hiện thuật toán duyệt đệ quy (Recursive Traversal) qua cấu trúc thư mục đã chọn.
- Định dạng đầu ra tối ưu cho AI (AI-Friendly Format): Khi gộp nội dung, mỗi file phải được phân tách rõ ràng bằng dấu định danh cấu trúc. Ví dụ:
  
  ---
  ### FILE: src/components/Button.tsx
  ---
  [Nội dung code của file Button.tsx ở đây]
  
  ---
  ### FILE: src/main.ts
  ---
  [Nội dung code của file main.ts ở đây]

3. Tính năng Tối ưu hóa & Tiện ích (Bắt buộc cho Prompt AI):
- Bộ đếm Token / Ký tự (Token/Character Counter): Ước tính tổng dung lượng text sau khi gộp để cảnh báo người dùng nếu nó vượt quá giới hạn Context Window của các AI thông dụng (như GPT-4, Claude 3.5, Gemini).
- Bộ lọc mặc định (Smart Ignore): Tự động loại bỏ các file nhị phân (ảnh, video) và các folder hệ thống/thư viện mặc định.
- Nút Action: "Copy to Clipboard" (Sao chép nhanh) và "Download File" (.txt/.md).

Hãy đề xuất cho tôi:
1. Stack công nghệ phù hợp (Ưu tiên giải pháp xử lý thuần Frontend bằng File System Access API của trình duyệt để bảo mật dữ liệu, hoặc kiến trúc Fullstack nếu cần lưu trữ).
2. Sơ đồ luồng dữ liệu (Data Flow) từ lúc User thả folder vào cho đến lúc xuất file đầu ra.
3. Viết code triển khai các component chính.
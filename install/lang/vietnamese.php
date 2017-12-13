<?php

define("_INSTALLER_TITLE", "e-Training - Cài đặt");
define("_NEXT", "Tiếp tục");
define("_BACK", "Quay lại");
define("_LOADING", "Đang tải");
define("_TRY_AGAIN", "Thử lại");
//--------------------------------------
define("_INTRODUCTION", "Lời giời thiệu");
define("_TITLE_STEP1", "Bước 1: Lựa chọn ngôn ngữ");
define("_LANGUAGE", "Ngôn ngữ");
define("_INSTALLER_INTRO_TEXT", "
e-Training là hệ thống đào tạo trực tuyến nội bộ do công ty Thành Công Á Châu phát triển dựa trên phần mềm mã nguồn mở forma.lms, đã được sử dụng bởi hàng trăm công ty trên thế giới.
	<p><b>Tính năng chính</b></p>
	<ul>
		<li>Hỗ trợ Scorm 1.2 và 2004</li>
		<li>Có thể cấu hình đáp ứng các mô hình đào tạo khác nhau (tự đào tạo, đào tạo hỗn hợp, học nhóm, học cộng đồng)</li>
		<li>Công cụ Kiểm duyện cho phép quản lý file, bài kiểm tra,trang web, câu hỏi v.v</li>
		<li>Tính năng học nhóm: <b>Diễn đàn</b>, <b>Wiki</b>, <b>Trò truyện</b>, <b>Quản lý dự án</b>, <b>Kho tài liệu</b></li>
		<li>Quản lý nhân lực và năng lực, phân tích thiếu hụt và lập kế hoạc phát triển con người</li>
		<li>Tạo chứng chỉ theo định dạng PDF</li>
		<li>Hỗ trợ tích hợp với hệ thống nhân sự của bên thứ ba(<b>SAP</b>, <b>Cezanne</b>, <b>Lotus Notes</b>, ...) và các giao thức (<b>LDAP</b>, <b>Active Directory</b>, <b>CRM</b>, <b>Erp</b> cũng như các giải pháp đặc thù)</li>
		<li>Hỗ trợ tính năng mạng xã hội như <b>Google Apps</b>, <b>Facebook</b>, <b>Twitter</b> e <b>Linkedin</b></li>
		<li>Hệ thống báo cáo tùy biến và báo cáo ra quyết định</li>
		<li>Tính năng phân quyền quản trị, theo chức năng, khu vực, quốc gia</li>
		<li>Hỗ trợ đa ngôn ngữ</li>
		<li>Hỗ trợ giao diện mobile</li>
	</ul>");
// ---------------------------------------
define("_TITLE_STEP2", "Bước 2: Kiểm tra hệ thống");
define("_SERVERINFO","Thông tin máy chủ");
define("_SERVER_SOFTWARE","Thông tin phần mềm : ");
define("_PHPVERSION","Phiên bản PHP : ");
define("_MYSQLCLIENT_VERSION","Phiên bản Mysql Client : ");
define("_LDAP","Ldap : ");
define("_ONLY_IF_YU_WANT_TO_USE_IT","Hãy cân nhắc nếu bạn muốn sử dụng LDAP ");
define("_OPENSSL","Openssl : ");
define("_WARINNG_SOCIAL","Hãy cân nhắc nếu bạn muốn đăng nhập từ mạng xã hội");
define("_MBSTRING","Hỗ trợ Multibyte");
define("_PHP_TIMEZONE","Múi giờ");

define("_PHPINFO","Thông tin PHP : ");
define("_MAGIC_QUOTES_GPC","magic_quotes_gpc : ");
define("_SAFEMODE","Safe mode : ");
define("_REGISTER_GLOBALS","register_global : ");
define("_ALLOW_URL_FOPEN","allow_url_fopen : ");
define("_ALLOW_URL_INCLUDE","allow_url_include : ");
define("_UPLOAD_MAX_FILESIZE","upload_max_filsize : ");
define("_POST_MAX_SIZE","post_max_size : ");
define("_MAX_EXECUTION_TIME","max_execution_time : ");
define("_ON","ON ");
define("_OFF","OFF ");

// -----------------------------------------
define("_TITLE_STEP3", "Bước 3: Bản quyền");
define("_AGREE_LICENSE", "Tôi đồng ý với các điiều khoản về bản quyền");
// -----------------------------------------
define("_TITLE_STEP4", "Bước 4: Cấu hình");
define("_SITE_BASE_URL", "Địa chỉ url của trang Web");
define("_DATABASE_INFO", "Thông tin cơ sở dữ liệu");
define("_DB_HOST", "Máy chủ");
define("_DB_NAME", "Tên cơ sở dữ liệu");
define("_DB_USERNAME", "Tên truy cập");
define("_DB_PASS", "Mật khẩu");
define("_UPLOAD_METHOD", "Phương thức tải file lên máy chủ (khuyến nghị FTP, nếu bạn sử dụng Window tại nhà, hãy chọn HTTP");
define("_HTTP_UPLOAD", "Phương pháp truyền thồng (HTTP)");
define("_FTP_UPLOAD", "Tải file lên bằng FTP");
define("_FTP_INFO", "Thông tin FTP");
define("_IF_FTP_SELECTED", "(Nếu bạn chọn tải file lên bằng FTP)");
define("_FTP_HOST", "Địa chỉ máy chủ");
define("_FTP_PORT", "Cổng");
define("_FTP_USERNAME", "Tên truy cập");
define("_FTP_PASS", "Mật khẩu");
define("_FTP_CONFPASS", "Xác nhận mật khẩu");
define("_FTP_PATH", "Đướng dẫn FTP (thư mục gốc để lưu file, ví dụ. /htdocs/ /mainfile_html/");
define("_CANT_CONNECT_WITH_DB", "Không thể kết nối đến cơ sở dữ liệu, xin kiểm tra lại thông tin");
define("_CANT_SELECT_DB", "Không tìm thấy cơ sở dữ liệu, xin kiểm tra lại thông tin");
define("_CANT_CONNECT_WITH_FTP","Không thể thiết lập kết nối FTP tới máy chủ, xin kiểm tra lại thông tin");
define("_SQL_STRICT_MODE_WARN", "Bạn đang bật MySQL ở <a href=\"http://dev.mysql.com/doc/en/server-sql-mode.html\" target=\"_blank\">chế độ hạn chế truy cập</a>; e-Training không hỗ trợ chế độ này.");
define("_SQL_STRICT_MODE", "MySQL <a href=\"http://dev.mysql.com/doc/en/server-sql-mode.html\" target=\"_blank\">chế độ hạn chế truy cập</a>");
// -----------------------------------------
define("_TITLE_STEP5", "Bước 5: Cấu hình");
define("_ADMIN_USER_INFO", "Thông tin quản trị");
define("_ADMIN_USERNAME", "Tên truy cập");
define("_ADMIN_FIRSTNAME", "Tên");
define("_ADMIN_LASTNAME", "Họ");
define("_ADMIN_PASS", "Mật khẩu");
define("_ADMIN_CONFPASS", "Xác nhận mật khẩu");
define("_ADMIN_EMAIL", "e-mail");
define("_LANG_TO_INSTALL", "Ngôn ngữ cài đặt");
define("_ADMIN_USERID_REQ", "Yêu cầu nhập tên đăng nhập");
define("_ADMIN_PASS_REQ", "Yêu cầu nhập mật khẩu");
define("_ADMIN_PASS_DOESNT_MATCH", "Mật khẩu không khớp");
define("_NO_LANG_SELECTED", "Chưa lụa chọn ngôn ngữ");

// -----------------------------------------
define("_TITLE_STEP6", "Bước 6: Cài đặt cơ sở dữ liệu");
define("_DATABASE", "Cơ sở dữ liệu");
define("_DB_IMPORTING", "Nhập dữ liệu");
define("_LANGUAGES", "Ngôn ngữ");
// -----------------------------------------
define("_TITLE_STEP7", "Step 7: Hoàn thành cài đặt");
define("_INSTALLATION_COMPLETED", "Cài đặt đã hoàn thành");
define("_INSTALLATION_DETAILS", "Chi tiết");
define("_SITE_HOMEPAGE", "Trang chủ");
define("_REVEAL_PASSWORD", "Xem mật khẩu");
define("_COMMUNITY", "Cộng đồng");
define("_COMMERCIAL_SERVICES", "Dịch vụ thương mại");
define("_CONFIG_FILE_NOT_SAVED", "Bộ cài không thể lưu file config.php file, hãy tải về và ghi đè trên máy chủ.");
define("_DOWNLOAD_CONFIG", "Tải file cấu hình");
define("_CHECKED_DIRECTORIES","Một số thư mục không tồn tại hoặc không thể truy cập");
define("_CHECKED_FILES","Một số file không thể truy cập");
// -----------------------------------------
define("_UPGRADER_TITLE", "e-Training - Nâng cấp");
define("_UPGRADE_CONFIG","Nâp cấp file config.php");
define("_UPG_CONFIG_OK","File config.php file được cập nhật thành công");
define("_UPG_CONFIG_NOT_CHANGED", "File config.php đã được cập nhật");
define("_UPG_CONFIG_NOT_SAVED", "Lỗi xảy ra khi cập nhật config.php.");
define("_UPGRADING", "Đang tiến hành nâng cấp");
define("_UPGRADING_LANGUAGES", "Ngôn ngữ nâng cấp");
define("_UPGRADE_COMPLETE", "Cập nhật hoàn thành");
define("_VERSION","Phiên bản e-Training");
define("_START","Đầu tiên");
define("_END","Cuối cùng");
define("_INVALID_CONFIG_FILE", "File config.php không hợp lệ; hãy kiểm tra phiên bản tại trường \"Start\"");
define("_UPGRADE_NOT_NEEDED","Bạn đã có phiên bản mới nhất của e-Training. Bỏ qua cập nhật.");


define("_MIME_CONTENT_TYPE", "mime_content_type() support");


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";




CREATE TABLE `DISTRICTS` (
  `ID` int(10) NOT NULL,
  `Name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;



INSERT INTO `DISTRICTS` (`ID`, `Name`) VALUES
(1, 'Quận 1'),
(2, 'Quận 2'),
(3, 'Quận 3');



CREATE TABLE `MOTEL` (
  `ID` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `area` int(11) DEFAULT NULL,
  `count_view` int(11) DEFAULT 0,
  `address` varchar(255) DEFAULT NULL,
  `latlng` varchar(255) DEFAULT NULL,
  `images` varchar(255) DEFAULT NULL,
  `user_id` int(10) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `utilities` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(255) DEFAULT NULL,
  `approve` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;



INSERT INTO `MOTEL` (`ID`, `title`, `description`, `price`, `area`, `count_view`, `address`, `latlng`, `images`, `user_id`, `category_id`, `district_id`, `utilities`, `created_at`, `phone`, `approve`) VALUES
(1, 'Phòng trọ giá rẻ gần chợ Bến Thành', 'Phòng sạch sẽ, thoáng mát, đầy đủ tiện nghi', 3000000, 25, 150, '123 Đường Lê Lợi, Quận 1', '10.7769,106.7009', 'motel1.jpg', 1, 1, 1, 'Điện, nước, wifi, máy lạnh', '2024-01-15 03:30:00', '0901234567', 1),
(2, 'Căn hộ mini cao cấp Thảo Điền', 'Căn hộ mới xây, view đẹp, an ninh tốt', 5500000, 35, 230, '456 Đường Xuân Thủy, Quận 2', '10.8031,106.7398', 'motel2.jpg', 2, 2, 2, 'Điện, nước, wifi, máy lạnh, thang máy', '2024-02-20 07:45:00', '0912345678', 1),
(3, 'Nhà trọ sinh viên giá sinh viên', 'Phòng rộng rãi, gần trường đại học', 2500000, 20, 95, '789 Đường Võ Văn Ngân, Quận 3', '10.7885,106.6970', 'motel3.jpg', 1, 1, 3, 'Điện, nước, wifi', '2024-03-10 02:15:00', '0923456789', 0);



CREATE TABLE `USER` (
  `ID` int(10) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` int(11) NOT NULL DEFAULT 0,
  `Phone` varchar(255) DEFAULT NULL,
  `Avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;



INSERT INTO `USER` (`ID`, `Name`, `Username`, `Email`, `Password`, `Role`, `Phone`, `Avatar`) VALUES
(1, 'Nguyễn Văn A', 'nguyenvana', 'nguyenvana@example.com', '123456', 0, '0901234567', 'avatar1.jpg'),
(2, 'Trần Thị B', 'tranthib', 'tranthib@example.com', '654321', 1, '0912345678', 'avatar2.jpg'),
(3, 'Lê Minh C', 'leminhc', 'leminhc@example.com', 'abcdef', 0, '0923456789', 'avatar3.jpg');


ALTER TABLE `DISTRICTS`
  ADD PRIMARY KEY (`ID`);


ALTER TABLE `MOTEL`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `district_id` (`district_id`);


ALTER TABLE `USER`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);


ALTER TABLE `USER`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


ALTER TABLE `MOTEL`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


ALTER TABLE `MOTEL`
  ADD CONSTRAINT `MOTEL_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `USER` (`ID`),
  ADD CONSTRAINT `MOTEL_ibfk_2` FOREIGN KEY (`district_id`) REFERENCES `DISTRICTS` (`ID`);
COMMIT;



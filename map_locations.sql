///////////////////////////////////////////////////////////////////////////////////CREATE TABLE IF NOT EXISTS `map_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `x_coordinate` float NOT NULL,
  `y_coordinate` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `map_locations` (`name`, `description`, `x_coordinate`, `y_coordinate`) VALUES
('Psychiatry Building A', 'Main psychiatric ward and consulting rooms.', 500, 400),
('Neurology Building B', 'Neurology testing and inpatient services.', 800, 300),
('General Medical', 'General Medical clinic, consulting rooms, and triage.', 200, 200),
('Main Gate', 'Public drop-off and entry point.', 100, 600),
('South Parking', 'Free visitor parking area.', 900, 700);

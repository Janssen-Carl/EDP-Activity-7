-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 05:43 PM
-- Server version: 8.0.44
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elibrary_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_sessions`
--

CREATE TABLE `api_sessions` (
  `SessionID` int NOT NULL,
  `SessionToken` char(64) NOT NULL,
  `User_ID` int NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ExpiresAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `api_sessions`
--

INSERT INTO `api_sessions` (`SessionID`, `SessionToken`, `User_ID`, `CreatedAt`, `ExpiresAt`) VALUES
(1, '902fada9da3a64ce36b11b2c8961cd1e33c26e12e74140003c3cc7091a18ffa4', 6, '2026-05-09 23:34:35', '2026-05-10 15:34:35'),
(2, '09f5546bf0af2bcb1ed7998fdc86b7151e43728683564d2e7a4681690f426795', 6, '2026-05-09 23:35:00', '2026-05-10 15:35:00'),
(3, 'fdbb04d39942c307d3f33feb17a73c80442e52fca03e92efb8157f6deda00744', 6, '2026-05-09 23:36:41', '2026-05-10 15:36:41'),
(4, '8342518b8f1748eb6384a2db573b77cdb88bad775e6c1bfb92b6b3834f3bd20d', 6, '2026-05-09 23:40:05', '2026-05-10 15:40:05'),
(5, 'ea10594c9f37a2f16178c8ead870c70ab6b32a5b380599cfc445175d91265d14', 6, '2026-05-09 23:40:25', '2026-05-10 15:40:25'),
(6, 'b6e63d1234b42ecf6160ffd0604fdd1d4793a132f72c07ecc585d3fce5ffd06e', 6, '2026-05-09 23:40:49', '2026-05-10 15:40:49'),
(7, '72f50905b2e41337213e48db6ce20b5de4d58f6dfc331969887dd32374a5225f', 6, '2026-05-09 23:41:40', '2026-05-10 15:41:40'),
(8, '44a1ccca06b78d64dfdc91ceef98d0d418a4ad748fc0be0b7b84149acb120482', 6, '2026-05-09 23:41:43', '2026-05-10 15:41:43');

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `BookID` int NOT NULL,
  `CategoryID` int DEFAULT NULL,
  `Author` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ISBN` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `BookTitle` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `BookType` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`BookID`, `CategoryID`, `Author`, `ISBN`, `BookTitle`, `BookType`) VALUES
(101, 1, 'Carl Sagan', '9780345331359', 'Cosmos', 'Digital'),
(102, 2, 'Jane Austen', '9780141439518', 'Pride and Prejudice', 'Physical'),
(103, 3, 'Bjarne Stroustrup', '9780321563842', 'The C++ Programming Language', 'Physical'),
(104, 4, 'George Polya', '9780691119700', 'How to Solve It', 'Digital'),
(105, 5, 'Winston Churchill', '9781250004749', 'The Churchill Factor', 'Physical'),
(106, 6, 'Leonardo da Vinci', '9780399588611', 'Leonardo: The Vinci Code', 'Physical'),
(107, 7, 'Plato', '9780140449150', 'The Republic', 'Digital'),
(108, 8, 'Sigmund Freud', '9780393314012', 'The Interpretation of Dreams', 'Physical'),
(109, 9, 'Peter Drucker', '9780060851132', 'Management Challenges for the 21st Century', 'Physical'),
(110, 10, 'Dale Carnegie', '9780671027032', 'How to Win Friends and Influence People', 'Digital'),
(111, 11, 'John Maynard Keynes', '9780140211189', 'The General Theory of Employment, Interest, and Money', 'Digital'),
(112, 12, 'Dr. Mehmet Oz', '9780743279177', 'You: The Owner\'s Manual', 'Physical'),
(113, 13, 'The Beatles', '9780671660351', 'The Beatles Anthology', 'Digital'),
(114, 14, 'Michael Jordan', '9780143125411', 'I Am The Greatest: The Muhammad Ali Story', 'Physical'),
(115, 15, 'Gordon Ramsay', '9781452160037', 'Kitchen Confidential', 'Physical'),
(116, 16, 'Lonely Planet', '9781786574017', 'Lonely Planet\'s Ultimate Travel Quiz', 'Digital'),
(117, 17, 'Dalai Lama', '9780399174375', 'The Art of Happiness', 'Physical'),
(118, 18, 'National Geographic', '9781426222285', 'National Geographic: The Photographs', 'Physical'),
(119, 19, 'J.K. Rowling', '9780545069670', 'Harry Potter and the Sorcerer\'s Stone', 'Physical'),
(120, 20, 'Robert Frost', '9780156837016', 'The Collected Poems of Robert Frost', 'Physical'),
(121, 21, 'Barack Obama', '9781524763169', 'A Promised Land', 'Digital'),
(122, 22, 'Nelson Mandela', '9780316548182', 'Long Walk to Freedom', 'Physical'),
(123, 23, 'Isaac Asimov', '9780553293357', 'The Foundation Trilogy', 'Physical'),
(124, 24, 'J.R.R. Tolkien', '9780261103573', 'The Hobbit', 'Physical'),
(125, 25, 'Stephen King', '9780450412955', 'The Shining', 'Digital'),
(126, 26, 'Agatha Christie', '9780062073488', 'The Murder of Roger Ackroyd', 'Digital'),
(127, 27, 'Nicholas Sparks', '9781455523374', 'The Longest Ride', 'Physical'),
(128, 28, 'Lee Child', '9780804178769', 'The Killing Floor', 'Digital'),
(129, 29, 'Jules Verne', '9780140449129', 'Twenty Thousand Leagues Under the Sea', 'Physical'),
(130, 30, 'Mark Twain', '9780451530325', 'The Adventures of Huckleberry Finn', 'Physical'),
(131, 31, 'William Shakespeare', '9780743477123', 'Macbeth', 'Digital'),
(132, 32, 'Arthur Conan Doyle', '9781853268971', 'The Adventures of Sherlock Holmes', 'Physical'),
(133, 33, 'Ken Follett', '9780452295275', 'The Pillars of the Earth', 'Digital'),
(134, 34, 'J.K. Rowling', '9781781105208', 'Harry Potter and the Chamber of Secrets', 'Physical'),
(135, 35, 'Harvard Law Review', '9781628103074', 'Harvard Law Review: Volume 131', 'Physical'),
(136, 36, 'Tom Wolfe', '9780316327083', 'The Right Stuff', 'Digital'),
(137, 37, 'Noam Chomsky', '9780241339016', 'Syntactic Structures', 'Physical'),
(138, 38, 'Stephen Hawking', '9780553380163', 'A Brief History of Time', 'Digital'),
(139, 39, 'Rachel Carson', '9780618249060', 'Silent Spring', 'Physical'),
(140, 40, 'Roger Penrose', '9780393040044', 'The Road to Reality', 'Digital'),
(141, 41, 'James Watson', '9780679760808', 'The Double Helix', 'Physical'),
(142, 42, 'Yann LeCun', '9780262046037', 'Deep Learning', 'Digital'),
(143, 43, 'Rodney Brooks', '9780262133123', 'How to Build a Robot', 'Physical'),
(144, 44, 'Andreas M. Antonopou', '9780988529903', 'The Digital Gold', 'Physical'),
(145, 45, 'Jon Duckett', '9781119000210', 'HTML and CSS: Design and Build Websites', 'Digital'),
(146, 46, 'Chris Griffith', '9780135040241', 'HTML5 & CSS3 For Dummies', 'Physical'),
(147, 47, 'Thomas Erl', '9780133387520', 'SOA: Principles of Service Design', 'Physical'),
(148, 48, 'Wes McKinney', '9781491957660', 'Python for Data Analysis', 'Physical'),
(149, 49, 'Aurélien Géron', '9781492032649', 'Hands-On Machine Learning with Scikit-Learn, Keras, and TensorFlow', 'Digital'),
(150, 50, 'Bruce Schneier', '9781119475905', 'Data and Goliath', 'Physical'),
(151, 1, 'John Doe', '978-0-123-45678-9', 'Introduction to Physics', 'Physical'),
(152, 1, 'Jane Smith', '978-1-234-56789-0', 'Advanced Chemistry', 'Digital'),
(153, 2, 'Emily Clark', '978-2-345-67890-1', 'Modern Literature', 'Physical'),
(154, 2, 'Oliver Wilson', '978-3-456-78901-2', 'Classic Novels', 'Digital'),
(155, 3, 'Michael Davis', '978-4-567-89012-3', 'Tech Innovations 2025', 'Physical'),
(156, 3, 'Sophia Brown', '978-5-678-90123-4', 'AI in the Modern World', 'Digital'),
(157, 4, 'William Harris', '978-6-789-01234-5', 'Mathematics for Beginners', 'Physical'),
(158, 4, 'Isabella Miller', '978-7-890-12345-6', 'Calculus Made Easy', 'Digital'),
(159, 5, 'David Thompson', '978-8-901-23456-7', 'History of the World', 'Physical'),
(160, 5, 'Olivia Garcia', '978-9-012-34567-8', 'Ancient Civilizations', 'Digital'),
(161, 6, 'Liam Martinez', '978-0-234-56789-1', 'Art of the Renaissance', 'Physical'),
(162, 6, 'Ava Robinson', '978-1-345-67890-2', 'Modern Art Movements', 'Digital'),
(163, 7, 'Jack Lewis', '978-2-456-78901-3', 'Philosophy of Ethics', 'Physical'),
(164, 7, 'Mia Walker', '978-3-567-89012-4', 'Logic and Reasoning', 'Digital'),
(165, 8, 'Ethan Moore', '978-4-678-90123-5', 'Introduction to Psychology', 'Physical'),
(166, 8, 'Grace Johnson', '978-5-789-01234-6', 'Cognitive Behavioral Therapy', 'Digital'),
(167, 9, 'James White', '978-6-890-12345-7', 'Entrepreneurship 101', 'Physical'),
(168, 9, 'Charlotte Anderson', '978-7-901-23456-8', 'Business Strategies for 2025', 'Digital'),
(169, 10, 'Benjamin Scott', '978-8-012-34567-9', 'Personal Growth and Success', 'Physical'),
(170, 10, 'Amelia Clark', '978-9-123-45678-0', 'Self-Help for the Modern Age', 'Digital'),
(171, 11, 'Lucas Rodriguez', '978-0-234-56789-2', 'Economic Theory Simplified', 'Physical'),
(172, 11, 'Lily Harris', '978-1-345-67890-3', 'Global Markets and Trends', 'Digital'),
(173, 12, 'Henry Taylor', '978-2-456-78901-4', 'Health and Wellness', 'Physical'),
(174, 12, 'Sophia White', '978-3-567-89012-5', 'Dieting and Nutrition', 'Digital'),
(175, 13, 'Alexander Miller', '978-4-678-90123-6', 'Understanding Music', 'Physical'),
(176, 13, 'Isabella Lee', '978-5-789-01234-7', 'Musical Theory for Beginners', 'Digital'),
(177, 14, 'Daniel Walker', '978-6-890-12345-8', 'Fitness for All Ages', 'Physical'),
(178, 14, 'Emma Robinson', '978-7-901-23456-9', 'Sports Science for Coaches', 'Digital'),
(179, 15, 'Sophie Green', '978-8-012-34567-0', 'Gourmet Cooking', 'Physical'),
(180, 15, 'Oliver Taylor', '978-9-123-45678-1', 'The Art of Baking', 'Digital'),
(181, 16, 'Lucas Young', '978-0-234-56789-3', 'Traveling the World', 'Physical'),
(182, 16, 'Charlotte King', '978-1-345-67890-4', 'The Best Places to Visit', 'Digital'),
(183, 17, 'Mason Parker', '978-2-456-78901-5', 'Religious Philosophy', 'Physical'),
(184, 17, 'Zoe Mitchell', '978-3-567-89012-6', 'History of Religion', 'Digital'),
(185, 18, 'Nathan Adams', '978-4-678-90123-7', 'Geography of the World', 'Physical'),
(186, 18, 'Ella Carter', '978-5-789-01234-8', 'World Atlas for the Curious', 'Digital'),
(187, 19, 'William Thomas', '978-6-890-12345-9', 'Children’s Stories', 'Physical'),
(188, 19, 'Ava Harris', '978-7-901-23457-0', 'Fairy Tales for Children', 'Digital'),
(189, 20, 'Benjamin Robinson', '978-8-012-34567-1', 'Poetry of the 20th Century', 'Physical'),
(190, 20, 'Grace Lewis', '978-9-123-45678-2', 'Modern Poetry', 'Digital'),
(191, 21, 'Lucas Scott', '978-0-234-56789-4', 'Politics in the 21st Century', 'Physical'),
(192, 21, 'Olivia Green', '978-1-345-67890-5', 'Global Political Systems', 'Digital'),
(193, 22, 'Michael Evans', '978-2-456-78901-6', 'The Biography of Great Leaders', 'Physical'),
(194, 22, 'Sophia Mitchell', '978-3-567-89012-7', 'Life of a Genius', 'Digital'),
(195, 23, 'Henry Allen', '978-4-678-90123-8', 'Science Fiction Odyssey', 'Physical'),
(196, 23, 'Isabella Parker', '978-5-789-01234-9', 'The Future of Science Fiction', 'Digital'),
(197, 24, 'James White', '978-6-890-12346-0', 'Fantasy Worlds Unleashed', 'Physical'),
(198, 24, 'Amelia Thomas', '978-7-901-23457-1', 'Magic and Mythology', 'Digital');

-- --------------------------------------------------------

--
-- Table structure for table `borrowing_record`
--

CREATE TABLE `borrowing_record` (
  `Record_ID` int NOT NULL,
  `BookID` int DEFAULT NULL,
  `User_ID` int DEFAULT NULL,
  `Status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Borrow_Date` date DEFAULT NULL,
  `Return_Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowing_record`
--

INSERT INTO `borrowing_record` (`Record_ID`, `BookID`, `User_ID`, `Status`, `Borrow_Date`, `Return_Date`) VALUES
(51, 101, 4, 'Returned', '2025-04-07', '2025-05-07'),
(52, 102, 9, 'Returned', '2025-04-07', '2025-05-07'),
(53, 103, 14, 'Returned', '2025-04-07', '2025-05-07'),
(54, 104, 19, 'Returned', '2025-04-07', '2025-05-07'),
(55, 105, 24, 'Returned', '2025-04-07', '2025-05-07'),
(56, 106, 29, 'Returned', '2025-04-07', '2025-05-07'),
(57, 107, 34, 'Returned', '2025-04-07', '2025-05-07'),
(58, 108, 39, 'Returned', '2025-04-07', '2025-05-07'),
(59, 109, 44, 'Returned', '2025-04-07', '2025-05-07'),
(60, 110, 49, 'Returned', '2025-04-07', '2025-05-07'),
(61, 101, 3, 'Borrowed', '2025-05-07', NULL),
(62, 102, 8, 'Borrowed', '2025-05-07', NULL),
(63, 103, 13, 'Borrowed', '2025-05-07', NULL),
(64, 104, 18, 'Borrowed', '2025-05-07', NULL),
(65, 105, 23, 'Borrowed', '2025-05-07', NULL),
(66, 106, 28, 'Borrowed', '2025-05-07', NULL),
(67, 107, 33, 'Borrowed', '2025-05-07', NULL),
(68, 108, 38, 'Borrowed', '2025-05-07', NULL),
(69, 109, 43, 'Borrowed', '2025-05-07', NULL),
(70, 110, 48, 'Borrowed', '2025-05-07', NULL),
(71, 102, 1, 'Returned', '2025-03-15', '2025-04-15'),
(72, 153, 6, 'Borrowed', '2025-05-01', NULL),
(73, 102, 11, 'Returned', '2025-02-20', '2025-03-20'),
(74, 153, 16, 'Returned', '2025-04-10', '2025-05-10'),
(75, 102, 20, 'Borrowed', '2025-05-15', NULL),
(76, 155, 3, 'Returned', '2025-03-01', '2025-04-01'),
(77, 103, 14, 'Returned', '2025-02-15', '2025-03-15'),
(78, 155, 22, 'Borrowed', '2025-05-05', NULL),
(79, 103, 26, 'Returned', '2025-01-20', '2025-02-20'),
(80, 159, 9, 'Returned', '2025-03-10', '2025-04-10'),
(81, 105, 18, 'Returned', '2025-02-25', '2025-03-25'),
(82, 159, 30, 'Borrowed', '2025-05-08', NULL),
(83, 105, 32, 'Returned', '2025-01-15', '2025-02-15'),
(84, 159, 36, 'Returned', '2025-04-20', '2025-05-20'),
(85, 123, 24, 'Returned', '2025-03-05', '2025-04-05'),
(86, 195, 28, 'Borrowed', '2025-05-12', NULL),
(87, 123, 34, 'Returned', '2025-04-15', '2025-05-15'),
(88, 124, 38, 'Returned', '2025-02-10', '2025-03-10'),
(89, 197, 40, 'Borrowed', '2025-05-18', NULL),
(90, 124, 42, 'Returned', '2025-03-20', '2025-04-20'),
(91, 119, 46, 'Returned', '2025-02-05', '2025-03-05'),
(92, 187, 48, 'Borrowed', '2025-05-20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int NOT NULL,
  `Category_Name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Description` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `Category_Name`, `Description`) VALUES
(1, 'Science', 'Books related to scientific topics'),
(2, 'Literature', 'Classic and modern literature'),
(3, 'Technology', 'Tech and IT related books'),
(4, 'Mathematics', 'Mathematical concepts and techniques'),
(5, 'History', 'Books about history and events'),
(6, 'Art', 'Art, drawing, and design books'),
(7, 'Philosophy', 'Books on philosophy and reasoning'),
(8, 'Psychology', 'Books about human behavior and mind'),
(9, 'Business', 'Books about business and entrepreneurship'),
(10, 'Self-Help', 'Books to improve personal development'),
(11, 'Economics', 'Books about the economy'),
(12, 'Health', 'Books about wellness and health'),
(13, 'Music', 'Books about music theory and instruments'),
(14, 'Sports', 'Books related to sports and fitness'),
(15, 'Cooking', 'Books on cooking recipes and techniques'),
(16, 'Travel', 'Books about travel and exploration'),
(17, 'Religion', 'Books related to religion and spirituality'),
(18, 'Geography', 'Books about geography and places'),
(19, 'Children', 'Books for children of all ages'),
(20, 'Poetry', 'Books on poetry and written art'),
(21, 'Politics', 'Books about politics and governance'),
(22, 'Biographies', 'Books about the lives of famous people'),
(23, 'Science Fiction', 'Fictional works based on science concepts'),
(24, 'Fantasy', 'Books in the fantasy genre'),
(25, 'Horror', 'Books on horror stories and thrillers'),
(26, 'Crime', 'Books about crime and investigations'),
(27, 'Romance', 'Romantic novels and stories'),
(28, 'Thriller', 'Books in the thriller genre'),
(29, 'Adventure', 'Adventure-filled books and stories'),
(30, 'Comedy', 'Books filled with humor and fun'),
(31, 'Drama', 'Dramatic and serious fiction'),
(32, 'Mystery', 'Mystery novels and stories'),
(33, 'Historical Fiction', 'Fiction based on historical events'),
(34, 'Anthology', 'Collections of short stories and poems'),
(35, 'Law', 'Books about legal practices and theory'),
(36, 'Journalism', 'Books on reporting and media'),
(37, 'Linguistics', 'Books about language and linguistics'),
(38, 'Astronomy', 'Books on space and celestial objects'),
(39, 'Environmental Studie', 'Books on the environment and nature'),
(40, 'Quantum Physics', 'Books on quantum mechanics and physics'),
(41, 'Genetics', 'Books about DNA, genes, and biology'),
(42, 'Artificial Intellige', 'Books about AI and machine learning'),
(43, 'Robotics', 'Books on robotics and automation'),
(44, 'Blockchain', 'Books about blockchain technology'),
(45, 'Web Development', 'Books about programming and web design'),
(46, 'Mobile Development', 'Books on mobile app development'),
(47, 'Cloud Computing', 'Books about cloud technologies'),
(48, 'Data Science', 'Books on data analysis and data science'),
(49, 'Machine Learning', 'Books on ML algorithms and models'),
(50, 'Cybersecurity', 'Books on digital security and protection');

-- --------------------------------------------------------

--
-- Table structure for table `digital_book`
--

CREATE TABLE `digital_book` (
  `BookID` int NOT NULL,
  `File_Format` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `File_Size` float(10,2) DEFAULT NULL,
  `File_Link` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `digital_book`
--

INSERT INTO `digital_book` (`BookID`, `File_Format`, `File_Size`, `File_Link`) VALUES
(101, 'PDF', 5.12, 'http://example.com/cosmos.pdf'),
(104, 'EPUB', 3.95, 'http://example.com/how_to_solve_it.epub'),
(107, 'PDF', 4.78, 'http://example.com/republic.pdf'),
(110, 'EPUB', 2.60, 'http://example.com/how_to_win_friends.epub'),
(111, 'PDF', 5.20, 'http://example.com/general_theory.pdf'),
(113, 'EPUB', 7.00, 'http://example.com/beatles_anthology.epub'),
(121, 'PDF', 3.25, 'http://example.com/promised_land.pdf'),
(125, 'EPUB', 4.60, 'http://example.com/the_shining.epub'),
(126, 'PDF', 5.45, 'http://example.com/murder_of_roger_ackroyd.pdf'),
(128, 'EPUB', 6.35, 'http://example.com/killing_floor.epub'),
(131, 'PDF', 2.85, 'http://example.com/macbeth.pdf'),
(133, 'EPUB', 7.20, 'http://example.com/pillars_of_the_earth.epub'),
(136, 'PDF', 4.30, 'http://example.com/right_stuff.pdf'),
(138, 'EPUB', 6.10, 'http://example.com/brief_history_of_time.epub'),
(140, 'PDF', 3.70, 'http://example.com/road_to_reality.pdf'),
(142, 'EPUB', 5.00, 'http://example.com/deep_learning.epub'),
(145, 'PDF', 3.50, 'http://example.com/html_and_css.pdf'),
(149, 'EPUB', 4.00, 'http://example.com/hands_on_machine_learning.epub'),
(152, 'PDF', 5.40, 'http://example.com/advanced_chemistry.pdf'),
(154, 'EPUB', 3.20, 'http://example.com/classic_novels.epub'),
(156, 'PDF', 6.10, 'http://example.com/ai_in_the_modern_world.pdf'),
(158, 'EPUB', 5.60, 'http://example.com/calculus_made_easy.epub'),
(160, 'PDF', 4.95, 'http://example.com/ancient_civilizations.pdf'),
(162, 'EPUB', 3.80, 'http://example.com/modern_art_movements.epub'),
(164, 'PDF', 2.90, 'http://example.com/logic_and_reasoning.pdf'),
(166, 'EPUB', 4.75, 'http://example.com/cognitive_behavioral_therapy.epub'),
(168, 'PDF', 3.55, 'http://example.com/business_strategies_2025.pdf'),
(170, 'EPUB', 4.20, 'http://example.com/self_help_for_modern_age.epub'),
(172, 'PDF', 6.25, 'http://example.com/global_markets_and_trends.pdf'),
(174, 'EPUB', 5.80, 'http://example.com/dieting_and_nutrition.epub'),
(176, 'PDF', 4.10, 'http://example.com/musical_theory_for_beginners.pdf'),
(178, 'EPUB', 5.30, 'http://example.com/sports_science_for_coaches.epub'),
(180, 'PDF', 3.95, 'http://example.com/art_of_baking.pdf'),
(182, 'EPUB', 4.40, 'http://example.com/best_places_to_visit.epub'),
(184, 'PDF', 6.05, 'http://example.com/history_of_religion.pdf'),
(186, 'EPUB', 4.55, 'http://example.com/world_atlas_for_the_curious.epub'),
(188, 'PDF', 3.85, 'http://example.com/fairy_tales_for_children.pdf'),
(190, 'EPUB', 5.20, 'http://example.com/modern_poetry.epub'),
(192, 'PDF', 4.65, 'http://example.com/global_political_systems.pdf'),
(194, 'EPUB', 5.10, 'http://example.com/life_of_a_genius.epub'),
(196, 'PDF', 7.15, 'http://example.com/future_of_science_fiction.pdf'),
(198, 'EPUB', 4.30, 'http://example.com/magic_and_mythology.epub');

-- --------------------------------------------------------

--
-- Table structure for table `guest_account`
--

CREATE TABLE `guest_account` (
  `User_ID` int NOT NULL,
  `AccessLevel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DateJoined` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `librarian_account`
--

CREATE TABLE `librarian_account` (
  `User_ID` int NOT NULL,
  `Role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DateHired` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logger`
--

CREATE TABLE `logger` (
  `LogID` int NOT NULL,
  `User_ID` int DEFAULT NULL,
  `DateLog` date NOT NULL,
  `TimeLog` time NOT NULL,
  `Action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logger`
--

INSERT INTO `logger` (`LogID`, `User_ID`, `DateLog`, `TimeLog`, `Action`) VALUES
(51, 6, '2026-05-09', '23:34:35', 'Logged In (API)'),
(52, 6, '2026-05-09', '23:35:00', 'Logged In (API)'),
(53, 6, '2026-05-09', '23:36:17', 'Requested Password Reset (API)'),
(54, 6, '2026-05-09', '23:36:41', 'Logged In (API)'),
(55, 6, '2026-05-09', '23:40:05', 'Logged In (API)'),
(56, 6, '2026-05-09', '23:40:25', 'Logged In (API)'),
(57, 6, '2026-05-09', '23:40:49', 'Logged In (API)'),
(58, 6, '2026-05-09', '23:41:40', 'Logged In (API)'),
(59, 6, '2026-05-09', '23:41:43', 'Logged In (API)');

-- --------------------------------------------------------

--
-- Table structure for table `member_account`
--

CREATE TABLE `member_account` (
  `User_ID` int NOT NULL,
  `MembershipLevel` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DateJoined` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_account`
--

INSERT INTO `member_account` (`User_ID`, `MembershipLevel`, `DateJoined`) VALUES
(6, 'Silver', '2026-05-09');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `ResetID` int NOT NULL,
  `User_ID` int NOT NULL,
  `TokenHash` char(64) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `Used` tinyint(1) NOT NULL DEFAULT '0',
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`ResetID`, `User_ID`, `TokenHash`, `ExpiresAt`, `Used`, `CreatedAt`) VALUES
(1, 6, '2435b283bd95a427a849f9c6ba618b5b8a9ffed424f14f426a5e90101fedf265', '2026-05-09 16:06:16', 0, '2026-05-09 23:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `physical_book`
--

CREATE TABLE `physical_book` (
  `BookID` int NOT NULL,
  `Shelf_Location` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Copies` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `physical_book`
--

INSERT INTO `physical_book` (`BookID`, `Shelf_Location`, `Copies`) VALUES
(102, 'Shelf 1A', 10),
(103, 'Shelf 1B', 5),
(105, 'Shelf 2A', 7),
(106, 'Shelf 2B', 6),
(108, 'Shelf 3A', 4),
(109, 'Shelf 3B', 8),
(112, 'Shelf 4A', 5),
(114, 'Shelf 4B', 6),
(115, 'Shelf 5A', 8),
(117, 'Shelf 5B', 7),
(118, 'Shelf 6A', 4),
(119, 'Shelf 6B', 10),
(120, 'Shelf 7A', 6),
(122, 'Shelf 7B', 5),
(123, 'Shelf 8A', 8),
(124, 'Shelf 8B', 9),
(127, 'Shelf 9A', 4),
(129, 'Shelf 9B', 7),
(130, 'Shelf 10A', 3),
(132, 'Shelf 10B', 6),
(134, 'Shelf 11A', 5),
(135, 'Shelf 11B', 3),
(137, 'Shelf 12A', 7),
(139, 'Shelf 12B', 5),
(141, 'Shelf 13A', 6),
(143, 'Shelf 13B', 8),
(144, 'Shelf 14A', 10),
(146, 'Shelf 14B', 4),
(147, 'Shelf 15A', 5),
(148, 'Shelf 15B', 6),
(150, 'Shelf 16A', 8),
(151, 'Shelf 16B', 10),
(153, 'Shelf 17A', 7),
(154, 'Shelf 17B', 6),
(155, 'Shelf 18A', 9),
(156, 'Shelf 18B', 5),
(157, 'Shelf 19A', 8),
(159, 'Shelf 19B', 4),
(161, 'Shelf 20A', 6),
(163, 'Shelf 20B', 7),
(165, 'Shelf 21A', 10),
(167, 'Shelf 21B', 6),
(169, 'Shelf 22A', 8),
(171, 'Shelf 22B', 5),
(173, 'Shelf 23A', 7),
(175, 'Shelf 23B', 9),
(177, 'Shelf 24A', 6),
(179, 'Shelf 24B', 8),
(181, 'Shelf 25A', 5),
(183, 'Shelf 25B', 7),
(185, 'Shelf 26A', 6),
(187, 'Shelf 26B', 4),
(189, 'Shelf 27A', 5),
(191, 'Shelf 27B', 6),
(193, 'Shelf 28A', 8),
(195, 'Shelf 28B', 7),
(197, 'Shelf 29A', 6);

-- --------------------------------------------------------

--
-- Table structure for table `user_credentials`
--

CREATE TABLE `user_credentials` (
  `User_ID` int NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `LastPasswordChange` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_credentials`
--

INSERT INTO `user_credentials` (`User_ID`, `PasswordHash`, `LastPasswordChange`) VALUES
(1, '$2y$10$demoHash1', '2026-05-09 23:17:49'),
(2, '$2y$10$demoHash2', '2026-05-09 23:17:49'),
(3, '$2y$10$demoHash3', '2026-05-09 23:17:49'),
(4, '$2y$10$demoHash4', '2026-05-09 23:17:49'),
(5, '$2y$10$demoHash5', '2026-05-09 23:17:49'),
(6, '$2y$12$.iMh16DsAezsbHe1sdheHOlRZbEK4CggVl8QOOpqS6WdC3wZ0eTs2', '2026-05-09 23:34:03');

-- --------------------------------------------------------

--
-- Table structure for table `user_elibrary`
--

CREATE TABLE `user_elibrary` (
  `User_ID` int NOT NULL COMMENT 'Unique user identifier',
  `FullName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User full name',
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email address for login and recovery',
  `PhoneNumber` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Contact phone number',
  `UserType` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Member' COMMENT 'Enum: Member, Librarian, Guest',
  `AccountStatus` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active' COMMENT 'Enum: Active, Inactive, Suspended',
  `DateJoined` date NOT NULL DEFAULT (curdate()) COMMENT 'Account creation date',
  `LastLogin` timestamp NULL DEFAULT NULL COMMENT 'Last login timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_elibrary`
--

INSERT INTO `user_elibrary` (`User_ID`, `FullName`, `Email`, `PhoneNumber`, `UserType`, `AccountStatus`, `DateJoined`, `LastLogin`) VALUES
(1, 'Juan Dela Cruz', 'member@library.edu', '09171234567', 'Member', 'Active', '2025-01-15', '2026-05-09 07:16:08'),
(2, 'Maria Santos', 'librarian@library.edu', '09182345678', 'Librarian', 'Active', '2024-11-20', '2026-05-09 07:16:08'),
(3, 'Carlos Reyes', 'carlos.reyes@example.com', '09293456789', 'Member', 'Active', '2025-03-10', '2026-05-09 07:16:08'),
(4, 'Angela Cruz', 'angela.cruz@example.com', '09304567890', 'Guest', 'Active', '2024-09-05', '2026-05-09 07:16:08'),
(5, 'Mark Bautista', 'mark.bautista@example.com', NULL, 'Member', 'Active', '2025-02-01', '2026-05-09 07:16:08'),
(6, 'Test', 'test@gmail.com', '09662887555', 'Member', 'Active', '2026-05-09', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_sessions`
--
ALTER TABLE `api_sessions`
  ADD PRIMARY KEY (`SessionID`),
  ADD UNIQUE KEY `uq_api_sessions_token` (`SessionToken`),
  ADD KEY `idx_api_sessions_user` (`User_ID`);

--
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`BookID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `borrowing_record`
--
ALTER TABLE `borrowing_record`
  ADD PRIMARY KEY (`Record_ID`),
  ADD KEY `BookID` (`BookID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Indexes for table `digital_book`
--
ALTER TABLE `digital_book`
  ADD PRIMARY KEY (`BookID`);

--
-- Indexes for table `guest_account`
--
ALTER TABLE `guest_account`
  ADD PRIMARY KEY (`User_ID`);

--
-- Indexes for table `librarian_account`
--
ALTER TABLE `librarian_account`
  ADD PRIMARY KEY (`User_ID`);

--
-- Indexes for table `logger`
--
ALTER TABLE `logger`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `UserID` (`User_ID`);

--
-- Indexes for table `member_account`
--
ALTER TABLE `member_account`
  ADD PRIMARY KEY (`User_ID`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`ResetID`),
  ADD UNIQUE KEY `uq_password_reset_token_hash` (`TokenHash`),
  ADD KEY `idx_password_reset_user` (`User_ID`);

--
-- Indexes for table `physical_book`
--
ALTER TABLE `physical_book`
  ADD PRIMARY KEY (`BookID`);

--
-- Indexes for table `user_credentials`
--
ALTER TABLE `user_credentials`
  ADD PRIMARY KEY (`User_ID`);

--
-- Indexes for table `user_elibrary`
--
ALTER TABLE `user_elibrary`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_email` (`Email`),
  ADD KEY `idx_user_type` (`UserType`),
  ADD KEY `idx_account_status` (`AccountStatus`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `api_sessions`
--
ALTER TABLE `api_sessions`
  MODIFY `SessionID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `BookID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `borrowing_record`
--
ALTER TABLE `borrowing_record`
  MODIFY `Record_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `logger`
--
ALTER TABLE `logger`
  MODIFY `LogID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `ResetID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_elibrary`
--
ALTER TABLE `user_elibrary`
  MODIFY `User_ID` int NOT NULL AUTO_INCREMENT COMMENT 'Unique user identifier', AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_sessions`
--
ALTER TABLE `api_sessions`
  ADD CONSTRAINT `api_sessions_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `book`
--
ALTER TABLE `book`
  ADD CONSTRAINT `book_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `digital_book`
--
ALTER TABLE `digital_book`
  ADD CONSTRAINT `digital_book_ibfk_1` FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `physical_book`
--
ALTER TABLE `physical_book`
  ADD CONSTRAINT `physical_book_ibfk_1` FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_credentials`
--
ALTER TABLE `user_credentials`
  ADD CONSTRAINT `user_credentials_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

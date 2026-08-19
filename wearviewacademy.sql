-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2026 at 07:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wearviewacademy`
--
CREATE DATABASE IF NOT EXISTS `wearviewacademy` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `wearviewacademy`;

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `ChangeSupportIssueStatus`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ChangeSupportIssueStatus` (IN `p_SupportIssueId` INT, IN `p_StatusId` INT, IN `p_AssignedTo` VARCHAR(255) CHARSET utf8mb4, IN `p_CompletionNotes` VARCHAR(500) CHARSET utf8mb4, IN `p_CompletedBy` VARCHAR(255) CHARSET utf8mb4)   BEGIN

    IF p_StatusId <> 3
    THEN
        UPDATE t_supportissue
        SET
            StatusId = p_StatusId,
            AssignedTo = p_AssignedTo
        WHERE
            Id = p_SupportIssueId;
    ELSE
        UPDATE t_supportissue
        SET
            CompletedDateTime = NOW(),
            CompletionNotes = p_CompletionNotes,
            CompletedBy = p_CompletedBy,
            StatusId = p_StatusId
        WHERE
            Id = p_SupportIssueId;
    END IF;

END$$

DROP PROCEDURE IF EXISTS `CreateSupportIssue`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateSupportIssue` (IN `p_UserId` INT, IN `p_Name` VARCHAR(100) CHARSET utf8mb4, IN `p_Email` VARCHAR(255) CHARSET utf8mb4, IN `p_FaultLocation` VARCHAR(255) CHARSET utf8mb4, IN `p_FaultTypeId` INT, IN `p_Title` VARCHAR(100) CHARSET utf8mb4, IN `p_Description` VARCHAR(255) CHARSET utf8mb4)  MODIFIES SQL DATA proc: BEGIN

    DECLARE v_OpenStatusId INT;

    /* Check for duplicate support ticket */
    IF EXISTS
    (
        SELECT 1
        FROM t_supportissue
        WHERE UserId = p_UserId
        AND FaultTypeId = p_FaultTypeId
        AND FaultLocation = p_FaultLocation
        AND IssueTitle = p_Title -- Fixed: changed p_IssueTitle to p_Title
    )
    THEN
        SELECT 'A support ticket with these details already exists.' AS Message;
        LEAVE proc;
    END IF;

    START TRANSACTION;

    /* Get Open status ID */
    SELECT Id
    INTO v_OpenStatusId
    FROM t_supportissuestatus
    WHERE Name = 'Open'
    LIMIT 1;

    /* Create support issue */
    INSERT INTO t_supportissue
    (
        UserId,
        FaultTypeId,
        StatusId,
        Name,
        Email,
        FaultLocation,
        IssueTitle,
        Description,
        IssueDateTime
    )
    VALUES
    (
        p_UserId,
        p_FaultTypeId,
        v_OpenStatusId,
        p_Name,
        p_Email,
        p_FaultLocation,
        p_Title, -- Fixed: changed p_IssueTitle to p_Title
        p_Description,
        NOW()
    );

    COMMIT;

    SELECT 'Support issue created successfully.' AS Message;
    
END$$

DROP PROCEDURE IF EXISTS `CreateUserSession`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateUserSession` (IN `p_UserId` INT)  MODIFIES SQL DATA BEGIN	
   
   DECLARE v_SessionId INT;

    -- Directly record the session against the user
    INSERT INTO t_session
    (
        UserId,
        LoginTime,
        LastActivity,
        IsActive
    )
    VALUES
    (
        p_UserId,
        NOW(),
        NOW(),
        TRUE
    );

    SET v_SessionId = LAST_INSERT_ID();

    -- Return the auto-increment SessionId to PHP
    SELECT v_SessionId AS SessionId;
    
END$$

DROP PROCEDURE IF EXISTS `EndSession`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `EndSession` (IN `p_UserId` INT)  MODIFIES SQL DATA BEGIN

    -- Update all active sessions directly for the user
    /*UPDATE t_session ts
    LEFT JOIN t_usersession tus ON tus.SessionId = ts.Id
    SET 
        ts.LogoutTime = NOW(),
        ts.LastActivity = NOW(),
        ts.IsActive = FALSE
    WHERE 
        (ts.UserId = p_UserId OR tus.UserId = p_UserId)
        AND ts.IsActive = TRUE;*/
        
        UPDATE t_session
    SET 
        LogoutTime = NOW(),
        LastActivity = NOW(),
        IsActive = FALSE
    WHERE 
        UserId = p_UserId
        AND (IsActive = TRUE OR IsActive = 1);
        
END$$

DROP PROCEDURE IF EXISTS `GetAllFaultTypes`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAllFaultTypes` ()  READS SQL DATA BEGIN

    SELECT 	Id, Fault
    FROM t_faulttype
    ORDER BY Fault ASC;

END$$

DROP PROCEDURE IF EXISTS `GetAllRoles`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAllRoles` ()  READS SQL DATA BEGIN

    SELECT Id, Name
    FROM t_role;
    
END$$

DROP PROCEDURE IF EXISTS `GetSupportIssues`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSupportIssues` (IN `p_StatusGroup` VARCHAR(50) CHARSET utf8mb4, IN `p_Search` VARCHAR(255) CHARSET utf8mb4, IN `p_ItemsPerPage` INT, IN `p_PageNo` INT)  READS SQL DATA BEGIN

    DECLARE v_Offset INT;

    SET v_Offset =
        (p_PageNo - 1) * p_ItemsPerPage;


    SELECT
        si.Id AS SupportIssueId,
        si.Name,
        si.Email,
        sis.Name AS `Status`,
        si.StatusId,
        si.IssueTitle,
        si.FaultLocation,
        si.Description,
        si.IssueDateTime,
        si.CompletedDateTime,
        si.AssignedTo,
        si.CompletionNotes,
        si.CompletedBy

    FROM t_supportissue si

    INNER JOIN t_supportissuestatus sis
        ON sis.Id = si.StatusId

    WHERE

        (
            (
                p_StatusGroup = 'Incomplete'
                AND si.StatusId IN (1, 2)
            )

            OR

            (
                p_StatusGroup = 'Completed'
                AND si.StatusId = 3
            )
        )

        AND

        (
            p_Search = ''

            OR si.Name LIKE CONCAT('%', p_Search, '%')

            OR si.IssueTitle LIKE CONCAT('%', p_Search, '%')

            OR si.FaultLocation LIKE CONCAT('%', p_Search, '%')

            OR si.Description LIKE CONCAT('%', p_Search, '%')
        )

    ORDER BY si.IssueDateTime DESC

    LIMIT p_ItemsPerPage
    OFFSET v_Offset;


    SELECT
        COUNT(*) AS TotalRecords

    FROM t_supportissue si

    INNER JOIN t_supportissuestatus sis
        ON sis.Id = si.StatusId

    WHERE

        (
            (
                p_StatusGroup = 'Incomplete'
                AND si.StatusId IN (1, 2)
            )

            OR

            (
                p_StatusGroup = 'Completed'
                AND si.StatusId = 3
            )
        )

        AND

        (
            p_Search = ''

            OR si.Name LIKE CONCAT('%', p_Search, '%')

            OR si.IssueTitle LIKE CONCAT('%', p_Search, '%')

            OR si.FaultLocation LIKE CONCAT('%', p_Search, '%')

            OR si.Description LIKE CONCAT('%', p_Search, '%')
        );

END$$

DROP PROCEDURE IF EXISTS `GetSupportIssuesByUserId`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSupportIssuesByUserId` (IN `p_UserId` INT, IN `p_Status` VARCHAR(50) CHARSET utf8mb4, IN `p_Search` VARCHAR(255) CHARSET utf8mb4, IN `p_ItemsPerPage` INT, IN `p_PageNo` INT)   BEGIN

    DECLARE v_Offset INT;
    SET v_Offset = (p_PageNo - 1) * p_ItemsPerPage;

    -- Clean up temporary table if left over from previous runs
    DROP TEMPORARY TABLE IF EXISTS tmp_support_issues;

    -- Store base filtered results in temporary table
    CREATE TEMPORARY TABLE tmp_support_issues AS
    SELECT
        si.Id AS SupportIssueId,
        si.Name,
        sis.Name AS `Status`,
        si.IssueTitle AS Title,
        si.FaultLocation,
        si.Description,
        si.IssueDateTime
    FROM t_supportissue si
    INNER JOIN t_supportissuestatus sis ON sis.Id = si.StatusId
    WHERE si.UserId = p_UserId;

    -- RESULT SET 1: Paged Records
    SELECT
        SupportIssueId,
        Name,
        `Status`,
        Title,
        FaultLocation,
        Description,
        IssueDateTime
    FROM tmp_support_issues
    WHERE (p_Status = 'All' OR `Status` = p_Status)
      AND (
            p_Search = ''
            OR Name LIKE CONCAT('%', p_Search, '%')
            OR Title LIKE CONCAT('%', p_Search, '%')
            OR FaultLocation LIKE CONCAT('%', p_Search, '%')
            OR Description LIKE CONCAT('%', p_Search, '%')
      )
    ORDER BY IssueDateTime DESC
    LIMIT p_ItemsPerPage
    OFFSET v_Offset;

    -- RESULT SET 2: Total Records Count
    SELECT
        COUNT(*) AS TotalRecords
    FROM tmp_support_issues
    WHERE (p_Status = 'All' OR `Status` = p_Status)
      AND (
            p_Search = ''
            OR Name LIKE CONCAT('%', p_Search, '%')
            OR Title LIKE CONCAT('%', p_Search, '%')
            OR FaultLocation LIKE CONCAT('%', p_Search, '%')
            OR Description LIKE CONCAT('%', p_Search, '%')
      );

    -- Cleanup
    DROP TEMPORARY TABLE IF EXISTS tmp_support_issues;
    
END$$

DROP PROCEDURE IF EXISTS `GetSupportIssueStatuses`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSupportIssueStatuses` ()  READS SQL DATA BEGIN

    SELECT Id, Name
    FROM t_supportissuestatus
    ORDER BY Id ASC;

END$$

DROP PROCEDURE IF EXISTS `GetUserDetails`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserDetails` (IN `p_Username` VARCHAR(255) CHARSET utf8mb4)  READS SQL DATA BEGIN

    SELECT 
        u.Id,
        u.Staffid,
        u.RoleId,
        u.Username,
        u.PasswordHash,
        s.FirstName,
        s.LastName,
        r.Name AS RoleName
    FROM t_user u
    LEFT JOIN t_staff s ON s.Id = u.Staffid
    LEFT JOIN t_role r  ON r.Id = u.RoleId
    WHERE u.Username = p_Username;

END$$

DROP PROCEDURE IF EXISTS `RegisterNewUser`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegisterNewUser` (IN `p_FirstName` VARCHAR(50) CHARSET utf8mb4, IN `p_LastName` VARCHAR(50) CHARSET utf8mb4, IN `p_Email` VARCHAR(255) CHARSET utf8mb4, IN `p_PasswordHash` VARCHAR(255) CHARSET utf8mb4, IN `p_RoleName` VARCHAR(50) CHARSET utf8mb4)  MODIFIES SQL DATA proc: BEGIN

    DECLARE v_StaffId  INT;
    DECLARE v_UserName VARCHAR(255);
    DECLARE v_RoleId   INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- 1. Roll back any partial inserts/updates
        ROLLBACK;

        -- 2. Return a clean, generic error message to the PHP UI
        SELECT 'An error occurred while creating the staff member.' AS Message;
    END;

    -- Check if user already exists
    IF EXISTS (
        SELECT 1
        FROM t_staff
        WHERE FirstName = p_FirstName
          AND LastName = p_LastName
          AND Email = p_Email
    ) THEN
        SELECT 'A staff member with these details already exists.' AS Message;
        LEAVE proc;
    END IF;

    -- Look up the Role ID based on the provided Role Name
    SELECT Id INTO v_RoleId 
    FROM t_role 
    WHERE Name = p_RoleName 
    LIMIT 1;

    -- Validate that the role actually exists
    IF v_RoleId IS NULL THEN
        SELECT CONCAT('Error: The specified role "', p_RoleName, '" does not exist.') AS Message;
        LEAVE proc;
    END IF;

    START TRANSACTION;

    INSERT INTO t_staff
    (
        FirstName,
        LastName,
        Email
    )
    VALUES
    (
        p_FirstName,
        p_LastName,
        p_Email
    );

    SET v_StaffId = LAST_INSERT_ID();

    SET v_UserName = CONCAT(
        LOWER(LEFT(p_FirstName, 1)),
        LOWER(p_LastName),
        FLOOR(100 + RAND() * 900)
    );

    -- Added RoleId to column list and values list
    INSERT INTO t_user
    (
        StaffId,
        UserName,
        PasswordHash,
        RoleId
    )
    VALUES
    (
        v_StaffId,
        v_UserName,
        p_PasswordHash,
        v_RoleId
    );

    COMMIT;

    SELECT
        'Staff member created successfully.' AS Message,
        v_UserName AS UserName;

END$$

DROP PROCEDURE IF EXISTS `UpdateUserActivity`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateUserActivity` (IN `p_UserId` INT)  MODIFIES SQL DATA BEGIN

    UPDATE 	t_session
    SET    	LastActivity = NOW()
    WHERE  	UserId = p_UserId
    AND 	IsActive = 1
    ORDER BY Id DESC
    LIMIT 1;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `t_faulttype`
--

DROP TABLE IF EXISTS `t_faulttype`;
CREATE TABLE `t_faulttype` (
  `Id` int(11) NOT NULL,
  `Fault` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `t_faulttype`
--

TRUNCATE TABLE `t_faulttype`;
--
-- Dumping data for table `t_faulttype`
--

INSERT INTO `t_faulttype` (`Id`, `Fault`) VALUES
(1, 'Classroom Computer'),
(2, 'Password Reset'),
(3, 'Printer Problem'),
(4, 'Wi-Fi Issue'),
(5, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `t_role`
--

DROP TABLE IF EXISTS `t_role`;
CREATE TABLE `t_role` (
  `Id` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `t_role`
--

TRUNCATE TABLE `t_role`;
--
-- Dumping data for table `t_role`
--

INSERT INTO `t_role` (`Id`, `Name`) VALUES
(1, 'Admin'),
(2, 'Staff');

-- --------------------------------------------------------

--
-- Table structure for table `t_session`
--

DROP TABLE IF EXISTS `t_session`;
CREATE TABLE `t_session` (
  `Id` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `LoginTime` datetime NOT NULL,
  `LastActivity` datetime NOT NULL,
  `LogoutTime` datetime DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `t_session`
--

TRUNCATE TABLE `t_session`;
--
-- Dumping data for table `t_session`
--

INSERT INTO `t_session` (`Id`, `UserId`, `LoginTime`, `LastActivity`, `LogoutTime`, `IsActive`) VALUES
(3, 13, '2026-08-17 21:51:55', '2026-08-17 21:51:55', '2026-08-17 23:16:39', 0),
(4, 13, '2026-08-17 21:53:00', '2026-08-17 23:02:33', '2026-08-17 23:16:39', 0),
(5, 14, '2026-08-17 23:12:07', '2026-08-17 23:17:01', '2026-08-18 05:07:40', 0),
(6, 14, '2026-08-17 23:13:39', '2026-08-17 23:17:01', '2026-08-17 23:17:01', 0),
(7, 14, '2026-08-17 23:16:57', '2026-08-17 23:17:01', '2026-08-17 23:17:01', 0),
(8, 14, '2026-08-17 23:17:56', '2026-08-18 01:17:12', '2026-08-18 01:17:12', 0),
(9, 15, '2026-08-18 04:30:11', '2026-08-18 04:43:25', '2026-08-18 05:07:54', 0),
(10, 15, '2026-08-18 05:08:25', '2026-08-18 05:08:43', '2026-08-18 05:08:43', 0),
(11, 14, '2026-08-18 05:14:07', '2026-08-18 05:31:04', '2026-08-18 05:31:04', 0),
(12, 14, '2026-08-18 05:45:15', '2026-08-18 05:49:28', '2026-08-18 05:49:28', 0);

-- --------------------------------------------------------

--
-- Table structure for table `t_staff`
--

DROP TABLE IF EXISTS `t_staff`;
CREATE TABLE `t_staff` (
  `Id` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `t_staff`
--

TRUNCATE TABLE `t_staff`;
--
-- Dumping data for table `t_staff`
--

INSERT INTO `t_staff` (`Id`, `FirstName`, `LastName`, `Email`) VALUES
(1, 'Katy', 'Johnson', 'katy.johnson@wearviewacademy.ac.uk'),
(2, 'David', 'Smith', 'david.smith@wearviewacademy.ac.uk'),
(10, 'Test', 'User', 'testuser@wearviewacademy.ac.uk'),
(11, 'New', 'Test', 'newtest@wearviewacademy.ac.uk'),
(12, 'Demo', 'User', 'demouser@wearviewacademy.ac.uk'),
(13, 'Bob', 'Johnson', 'bob.johnson@wearviewacademy.ac.uk'),
(14, 'Jay', 'Grando', 'jay.grando@wearviewacademy.ac.uk'),
(15, 'Jane', 'Wilhelm', 'jane.wilhelm@wearviewacademy.ac.uk');

-- --------------------------------------------------------

--
-- Table structure for table `t_supportissue`
--

DROP TABLE IF EXISTS `t_supportissue`;
CREATE TABLE `t_supportissue` (
  `Id` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `FaultTypeId` int(11) NOT NULL,
  `StatusId` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `FaultLocation` varchar(255) NOT NULL,
  `IssueTitle` varchar(100) NOT NULL,
  `Description` varchar(500) DEFAULT NULL,
  `IssueDateTime` datetime NOT NULL,
  `AssignedTo` varchar(255) DEFAULT NULL,
  `CompletedDateTime` datetime DEFAULT NULL,
  `CompletionNotes` varchar(500) DEFAULT NULL,
  `CompletedBy` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `t_supportissue`
--

TRUNCATE TABLE `t_supportissue`;
--
-- Dumping data for table `t_supportissue`
--

INSERT INTO `t_supportissue` (`Id`, `UserId`, `FaultTypeId`, `StatusId`, `Name`, `Email`, `FaultLocation`, `IssueTitle`, `Description`, `IssueDateTime`, `AssignedTo`, `CompletedDateTime`, `CompletionNotes`, `CompletedBy`) VALUES
(1, 13, 3, 1, 'Bob Johnson', 'bob.johnson@wearviewacademy.ac.uk', 'Room 12', 'Problem with printer', 'There is a problem with the printer itself. I\'m not so sure what it may be.', '2026-08-17 22:38:19', NULL, NULL, NULL, NULL),
(2, 13, 2, 3, 'Bob Johnson', 'bob.johnson@wearviewacademy.ac.uk', 'Room 14', 'Account locked', 'My account is locked as I have neutered the password wrong three times. Please help to unlock my account an reset my password.', '2026-08-17 22:43:46', 'Jay Grando', '2026-08-18 00:40:22', 'The user\'s account was unlocked and a new password was issued.', 'Jay Grando'),
(3, 14, 5, 1, 'Jay Grando', 'jay.grando@wearviewacademy.ac.uk', 'Room 1', 'Broken Bulb', 'Bulb is broken and needs replacement.', '2026-08-18 01:15:40', NULL, NULL, NULL, NULL),
(4, 15, 4, 3, 'Jane Wilhelm', 'jane.wilhelm@wearviewacademy.ac.uk', 'Reception', 'Network Issue', 'Network issue in reception.', '2026-08-18 04:32:19', 'Jay Grando', '2026-08-18 05:26:43', 'This job has been resolved. Found the Ethernet cable was unplugged from the PC.', 'Jay Grando');

-- --------------------------------------------------------

--
-- Table structure for table `t_supportissuestatus`
--

DROP TABLE IF EXISTS `t_supportissuestatus`;
CREATE TABLE `t_supportissuestatus` (
  `Id` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `t_supportissuestatus`
--

TRUNCATE TABLE `t_supportissuestatus`;
--
-- Dumping data for table `t_supportissuestatus`
--

INSERT INTO `t_supportissuestatus` (`Id`, `Name`) VALUES
(1, 'Open'),
(2, 'In Progress'),
(3, 'Resolved');

-- --------------------------------------------------------

--
-- Table structure for table `t_user`
--

DROP TABLE IF EXISTS `t_user`;
CREATE TABLE `t_user` (
  `Id` int(11) NOT NULL,
  `Staffid` int(11) NOT NULL,
  `RoleId` int(11) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Truncate table before insert `t_user`
--

TRUNCATE TABLE `t_user`;
--
-- Dumping data for table `t_user`
--

INSERT INTO `t_user` (`Id`, `Staffid`, `RoleId`, `Username`, `PasswordHash`) VALUES
(7, 1, 2, 'staffmember', '$2y$10$wT3G9N6d3B.Xv/zL8J2V/O1Y3K2R5M6N7O8P9Q0R1S2T3U4V5W6X.'),
(8, 2, 1, 'admin', '$2y$10$e8F01xN3y9A2B1C0D2E3Fu1G2H3I4J5K6L7M8N9O0P1Q2R3S4T5U6'),
(10, 10, 1, 'tuser147', '$2y$10$oN79LHcwcFt2qL45uJx3/eap2vuH8QqWteoRk43zEgTcMoXilgW/e'),
(11, 11, 2, 'ntest574', '$2y$10$3.SJTZyi0ARhFYsQvHg3heOH0z0slKX3RpnoyWT.e1jPqH/sOrgS2'),
(12, 12, 2, 'duser474', '$2y$10$b4QKlPGutafZvk1GSVsK5.2yESE3JFwqPxo2nbvN6HpTKorxryx86'),
(13, 13, 2, 'bjohnson509', '$2y$10$Apy54c7bSpzLU5ZvqLJMIeOocleWIipE4/ynH2Xv/0TFd8xBSJqIa'),
(14, 14, 1, 'jgrando617', '$2y$10$vTtGsZKERIuogHd/Xj2WQ.mZ5ddXJ/VMlDItvvrEy7uiqQ.mP8InK'),
(15, 15, 2, 'jwilhelm622', '$2y$10$4HK1eN49PdE3bdPDwmYvXOWzLhevZHCn8ufP8BjOvcqvhHUX1q1sq');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `t_faulttype`
--
ALTER TABLE `t_faulttype`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `t_role`
--
ALTER TABLE `t_role`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `t_session`
--
ALTER TABLE `t_session`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `fk_session_userid_user_id` (`UserId`);

--
-- Indexes for table `t_staff`
--
ALTER TABLE `t_staff`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `t_supportissue`
--
ALTER TABLE `t_supportissue`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `fk_supportissue_userid_user_id` (`UserId`),
  ADD KEY `fk_supportissue_faulttypeid_faulttype_id` (`FaultTypeId`),
  ADD KEY `fk_supportissue_statusid_supportissuestatus_id` (`StatusId`);

--
-- Indexes for table `t_supportissuestatus`
--
ALTER TABLE `t_supportissuestatus`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `t_user`
--
ALTER TABLE `t_user`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `fk_user_staffid_staff_id` (`Staffid`),
  ADD KEY `fk_user_roleid_role_id` (`RoleId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `t_faulttype`
--
ALTER TABLE `t_faulttype`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `t_role`
--
ALTER TABLE `t_role`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `t_session`
--
ALTER TABLE `t_session`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `t_staff`
--
ALTER TABLE `t_staff`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `t_supportissue`
--
ALTER TABLE `t_supportissue`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `t_supportissuestatus`
--
ALTER TABLE `t_supportissuestatus`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `t_user`
--
ALTER TABLE `t_user`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `t_session`
--
ALTER TABLE `t_session`
  ADD CONSTRAINT `fk_session_userid_user_id` FOREIGN KEY (`UserId`) REFERENCES `t_user` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_supportissue`
--
ALTER TABLE `t_supportissue`
  ADD CONSTRAINT `fk_supportissue_statusid_supportissuestatus_id` FOREIGN KEY (`StatusId`) REFERENCES `t_supportissuestatus` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_user`
--
ALTER TABLE `t_user`
  ADD CONSTRAINT `fk_user_roleid_role_id` FOREIGN KEY (`RoleId`) REFERENCES `t_role` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_staffid_staff_id` FOREIGN KEY (`Staffid`) REFERENCES `t_staff` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

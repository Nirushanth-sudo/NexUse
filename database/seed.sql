-- NexUse — demo seed data
--
-- Run:  mysql -u nexuse_app -p nexuse < database\seed.sql
--
-- Every seeded account uses the password:  Passw0rd!
-- (bcrypt hash below is the same for all of them, generated with password_hash()).

USE nexuse;

-- Clear any existing data before seeding.
--
-- DELETE, not TRUNCATE: MySQL refuses to TRUNCATE a table referenced by a foreign
-- key even when FOREIGN_KEY_CHECKS is off (TRUNCATE is DDL, so the bypass does not
-- apply — error #1701). DELETE honours the bypass, so this works on a re-import.
-- The AUTO_INCREMENT reset afterwards keeps ids starting from 1.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM messages;
DELETE FROM conversations;
DELETE FROM notifications;
DELETE FROM complaints;
DELETE FROM reviews;
DELETE FROM requests;
DELETE FROM listing_images;
DELETE FROM listings;
DELETE FROM categories;
DELETE FROM users;

ALTER TABLE messages       AUTO_INCREMENT = 1;
ALTER TABLE conversations  AUTO_INCREMENT = 1;
ALTER TABLE notifications  AUTO_INCREMENT = 1;
ALTER TABLE complaints     AUTO_INCREMENT = 1;
ALTER TABLE reviews        AUTO_INCREMENT = 1;
ALTER TABLE requests       AUTO_INCREMENT = 1;
ALTER TABLE listing_images AUTO_INCREMENT = 1;
ALTER TABLE listings       AUTO_INCREMENT = 1;
ALTER TABLE categories     AUTO_INCREMENT = 1;
ALTER TABLE users          AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;


-- ---------------------------------------------------------------- users ----
INSERT INTO users (user_id, name, email, password_hash, phone, address, city, bio, role, status, created_at) VALUES
(1, 'System Admin',   'admin@nexuse.lk', '$2y$12$MZ9tf1ZQUckCdGrNYrhX7.ybla82yq/yh77JiMMuXLmK3WcKMDpfm', '0112345678', 'NexUse HQ, Union Place', 'Colombo',    'Platform administrator.',                                   'admin',  'active', '2026-06-01 09:00:00'),
(2, 'Nimali Perera',  'nimali@example.lk', '$2y$12$MZ9tf1ZQUckCdGrNYrhX7.ybla82yq/yh77JiMMuXLmK3WcKMDpfm', '0771234567', '42 Galle Road',          'Colombo',    'Clearing out a flat. Happy to lend tools to neighbours.',    'member', 'active', '2026-06-04 10:15:00'),
(3, 'Kasun Silva',    'kasun@example.lk', '$2y$12$MZ9tf1ZQUckCdGrNYrhX7.ybla82yq/yh77JiMMuXLmK3WcKMDpfm', '0759876543', '17 Temple Lane',         'Kandy',      'Photographer. Rent out gear between shoots.',                'member', 'active', '2026-06-07 14:40:00'),
(4, 'Fathima Rizwan', 'fathima@example.lk', '$2y$12$MZ9tf1ZQUckCdGrNYrhX7.ybla82yq/yh77JiMMuXLmK3WcKMDpfm','0723456789', '8 Lake Crescent',        'Galle',      'Teacher. Mostly donating books and school supplies.',        'member', 'active', '2026-06-11 08:05:00'),
(5, 'Dilan Fernando', 'dilan@example.lk', '$2y$12$MZ9tf1ZQUckCdGrNYrhX7.ybla82yq/yh77JiMMuXLmK3WcKMDpfm', '0768765432', '95 Hill Street',         'Negombo',    'Student. Looking for affordable second-hand equipment.',     'member', 'active', '2026-06-18 19:20:00'),
(6, 'Tharindu Bandara','tharindu@example.lk', '$2y$12$MZ9tf1ZQUckCdGrNYrhX7.ybla82yq/yh77JiMMuXLmK3WcKMDpfm','0701122334','3 Station Road',        'Kurunegala', 'Runs a small repair shop. Buys and fixes broken electronics.','member','active', '2026-07-02 11:30:00');


-- ----------------------------------------------------------- categories ----
INSERT INTO categories (category_id, name, slug) VALUES
(1,  'Electronics',        'electronics'),
(2,  'Furniture',          'furniture'),
(3,  'Books & Stationery', 'books-stationery'),
(4,  'Tools & Equipment',  'tools-equipment'),
(5,  'Clothing',           'clothing'),
(6,  'Sports & Outdoor',   'sports-outdoor'),
(7,  'Kitchen & Home',     'kitchen-home'),
(8,  'Baby & Kids',        'baby-kids'),
(9,  'Musical Instruments','musical-instruments'),
(10, 'Vehicles & Parts',   'vehicles-parts');


-- ------------------------------------------------------------- listings ----
INSERT INTO listings (listing_id, user_id, category_id, title, description, item_condition, listing_type, price, location, status, created_at) VALUES
-- for sale
(1,  2, 1, 'Dell Inspiron 15 laptop',              'Used for two years for office work. Core i5, 8 GB RAM, 512 GB SSD. Battery holds about three hours. Charger included, no bag.', 'good',     'sell',   85000.00, 'Colombo',    'available', '2026-07-14 09:10:00'),
(2,  6, 1, 'Samsung 32" LED television',           'Working perfectly, upgraded to a larger set. Remote and wall bracket included. Minor scratch on the back panel that does not show.', 'good',     'sell',   32000.00, 'Kurunegala', 'available', '2026-07-16 16:45:00'),
(3,  2, 2, 'Teak writing desk',                    'Solid teak, three drawers, one sticky runner. Roughly 120 x 60 cm. Buyer arranges collection from Colombo 3.',                      'fair',     'sell',   18500.00, 'Colombo',    'available', '2026-07-18 11:20:00'),
(4,  5, 6, 'Mountain bike, 21 speed',              'Ridden through university, serviced in June. New brake pads and tyres. Frame size medium, some paint chips on the crossbar.',       'good',     'sell',   24000.00, 'Negombo',    'available', '2026-07-21 08:35:00'),
(5,  3, 1, 'Canon EF 50mm f/1.8 lens',             'Sharp little prime, no fungus or haze. Front and rear caps included. Selling because I moved to a zoom.',                            'like_new', 'sell',   28000.00, 'Kandy',      'reserved',  '2026-07-22 13:05:00'),
(6,  6, 7, 'Gas cooker, three burner',             'Wedding gift, never taken out of the box. Sealed, with the manual and warranty card. Regulator not included.',                     'new',      'sell',    9500.00, 'Kurunegala', 'available', '2026-07-25 10:00:00'),

-- for rent
(7,  3, 1, 'Canon EOS 90D camera body',            'Professional body available for daily rental. Two batteries and a 64 GB card included. Renter covers any damage.',                  'like_new', 'rent',    3500.00, 'Kandy',      'available', '2026-07-15 12:00:00'),
(8,  2, 4, 'Bosch rotary hammer drill',            'Heavy-duty drill for concrete work. Comes with four bits. Rented by the day, deposit discussed on acceptance.',                      'good',     'rent',     900.00, 'Colombo',    'available', '2026-07-19 15:30:00'),
(9,  3, 9, 'Yamaha acoustic guitar',               'Good practice guitar for a student or a one-off performance. New strings fitted in July. Soft case included.',                       'good',     'rent',     600.00, 'Kandy',      'available', '2026-07-23 17:10:00'),
(10, 5, 6, 'Two-person camping tent',              'Waterproof, easy pitch, used on three trips. Pegs and groundsheet in the bag. Ideal for a weekend.',                                 'good',     'rent',     750.00, 'Negombo',    'reserved',  '2026-07-24 09:45:00'),
(11, 6, 4, 'Pressure washer, 1800 W',              'For driveways and vehicles. Twelve metre hose. Rented by the day, collected from the shop.',                                         'good',     'rent',    1200.00, 'Kurunegala', 'available', '2026-07-28 14:20:00'),

-- free lending / sharing
(12, 4, 3, 'A-Level combined maths past papers',   'Full set of past papers and marking schemes, 2015 to 2024. Borrow for a term and return before the exams.',                          'good',     'share',      NULL, 'Galle',      'available', '2026-07-17 07:50:00'),
(13, 2, 4, 'Folding aluminium ladder',             'Two metre folding ladder. Borrow it for a day or two, just bring it back clean.',                                                    'good',     'share',      NULL, 'Colombo',    'available', '2026-07-26 16:00:00'),
(14, 4, 8, 'Baby cot with mattress',               'Our daughter has outgrown it. Happy to lend to a family who needs it for a few months, then pass it on again.',                      'good',     'share',      NULL, 'Galle',      'reserved',  '2026-07-27 11:15:00'),
(15, 3, 1, 'Projector for events',                 'Lend for school events and small functions. HDMI and VGA. Please return the remote with it, I have lost two already.',               'fair',     'share',      NULL, 'Kandy',      'available', '2026-07-30 18:40:00'),

-- donations
(16, 4, 3, 'Box of primary school storybooks',     'About forty illustrated readers for ages five to nine. Free to a school, library or family who will use them.',                      'good',     'donate',     NULL, 'Galle',      'available', '2026-07-20 08:00:00'),
(17, 2, 5, 'Winter clothing bundle',               'Jackets and jumpers, adult medium, from a trip abroad. No use for them here. Free to anyone travelling or working in the hills.',     'good',     'donate',     NULL, 'Colombo',    'available', '2026-07-29 12:30:00'),
(18, 6, 1, 'Working desktop computer',             'Older Core i3 tower, runs Linux comfortably. Donating to a student who needs a machine for coursework. No monitor.',                  'fair',     'donate',     NULL, 'Kurunegala', 'completed', '2026-07-31 09:25:00'),
(19, 4, 7, 'Kitchen crockery set',                 'Plates, bowls and mugs, twenty-four pieces, two chipped. Free to a family setting up a home.',                                       'fair',     'donate',     NULL, 'Galle',      'available', '2026-08-03 10:10:00'),
(20, 5, 3, 'Engineering textbooks, first year',    'Mechanics, thermodynamics and materials. Free to a first-year student. Some pencil notes in the margins.',                            'good',     'donate',     NULL, 'Negombo',    'available', '2026-08-05 15:55:00');


-- ------------------------------------------------------------- requests ----
-- Every status is represented so the demo can show each state.
INSERT INTO requests (request_id, listing_id, requester_id, owner_id, request_type, message, status, start_date, return_date, actual_return_date, return_condition, owner_note, created_at) VALUES
-- pending
(1,  1,  5, 2, 'buy',      'Is the battery original? I can collect from Colombo this weekend.',            'pending',   NULL,         NULL,         NULL,         NULL,       NULL,                                    '2026-08-10 09:30:00'),
(2,  8,  6, 2, 'rent',     'I need it for a day of concrete drilling on the 20th. Happy to pay a deposit.','pending',   '2026-08-20', '2026-08-21', NULL,         NULL,       NULL,                                    '2026-08-12 14:05:00'),
(3,  16, 5, 4, 'donation', 'I volunteer at a reading club in Negombo. These would be very welcome.',       'pending',   NULL,         NULL,         NULL,         NULL,       NULL,                                    '2026-08-13 18:20:00'),

-- accepted (in progress)
(4,  5,  6, 3, 'buy',      'I will take it at the asking price. When can I collect from Kandy?',           'accepted',  NULL,         NULL,         NULL,         NULL,       'Collect any evening after six.',        '2026-08-06 11:00:00'),
(5,  10, 2, 5, 'rent',     'Camping trip to Ella. Two nights, will bring it back dry.',                    'accepted',  '2026-08-15', '2026-08-18', NULL,         NULL,       'Please check the poles before you go.', '2026-08-08 16:45:00'),
(6,  14, 5, 4, 'borrow',   'Expecting in October. Would three months be alright?',                         'accepted',  '2026-08-14', '2026-11-14', NULL,         NULL,       'Of course, take your time.',            '2026-08-11 10:25:00'),

-- rejected
(7,  7,  5, 3, 'rent',     'Can I have it for the whole of next week at a discount?',                      'rejected',  '2026-08-17', '2026-08-24', NULL,         NULL,       'Sorry, I have a booking that week.',    '2026-08-09 13:15:00'),

-- withdrawn
(8,  3,  6, 2, 'buy',      'Interested in the desk if the drawer can be fixed.',                           'withdrawn', NULL,         NULL,         NULL,         NULL,       NULL,                                    '2026-08-07 08:50:00'),

-- completed (these unlock reviews)
(9,  18, 5, 6, 'donation', 'I am a second-year student and my laptop died. This would help a lot.',        'completed', NULL,         NULL,         '2026-08-04', 'as_given', 'Handed over on Sunday. Good luck!',     '2026-08-01 09:40:00'),
(10, 9,  4, 3, 'rent',     'Needed for a school concert on the 2nd. Returning the next morning.',          'completed', '2026-08-01', '2026-08-03', '2026-08-03', 'as_given', 'Returned on time, no issues.',          '2026-07-29 15:10:00'),
(11, 13, 6, 2, 'borrow',   'Painting the shop front. One day is plenty.',                                  'completed', '2026-07-30', '2026-07-31', '2026-07-31', 'as_given', 'Came back cleaner than it went out.',   '2026-07-28 17:35:00'),
(12, 12, 5, 4, 'borrow',   'Sitting A-Levels in December. Will return them straight after.',               'completed', '2026-07-18', '2026-08-02', '2026-08-02', 'minor_damage','One paper has a torn corner, no matter.','2026-07-17 12:00:00');


-- -------------------------------------------------------------- reviews ----
INSERT INTO reviews (review_id, request_id, reviewer_id, reviewee_id, rating, comment, created_at) VALUES
(1, 9,  5, 6, 5, 'Tharindu did not have to do this and he did it anyway. Machine works perfectly and he even installed the OS for me.', '2026-08-05 10:00:00'),
(2, 9,  6, 5, 5, 'Polite, turned up when he said he would. Glad it went to someone who needed it.',                                     '2026-08-05 14:30:00'),
(3, 10, 4, 3, 5, 'Guitar was freshly strung and sounded lovely. Kasun met me at the school to save a trip.',                            '2026-08-04 09:15:00'),
(4, 10, 3, 4, 4, 'Returned on time and in good order. Would lend to Fathima again.',                                                    '2026-08-04 19:50:00'),
(5, 11, 6, 2, 5, 'Ladder was exactly as described and Nimali was flexible about the pickup time.',                                      '2026-08-02 08:20:00'),
(6, 12, 4, 5, 4, 'Papers came back on the agreed day. Small tear on one sheet but he told me about it upfront, which I appreciated.',   '2026-08-03 16:40:00');


-- ----------------------------------------------------------- complaints ----
INSERT INTO complaints (complaint_id, complainant_id, against_user_id, listing_id, subject, description, status, admin_note, created_at) VALUES
(1, 5, 3, 7,  'Listing withdrawn after I was told it was mine', 'I agreed a rental for the camera body and was told to collect it, then the request was rejected the next day without explanation. I had already turned down another option.', 'reviewing', 'Contacted both parties on 14 August, awaiting the owner''s response.', '2026-08-12 20:15:00'),
(2, 2, 6, NULL,'Item returned in worse condition than described','The drill came back with a cracked chuck guard. It was not mentioned and I only found it when I next used it. Raising it here as the platform advises rather than arguing directly.', 'open', NULL, '2026-08-14 07:45:00');


-- -------------------------------------------------------- notifications ----
INSERT INTO notifications (notification_id, user_id, type, title, message, link, is_read, created_at) VALUES
(1,  2, 'request',   'New request on your listing',        'Dilan Fernando requested to buy "Dell Inspiron 15 laptop".',            '/requests/detail.php?id=1',  0, '2026-08-10 09:30:00'),
(2,  2, 'request',   'New request on your listing',        'Tharindu Bandara requested to rent "Bosch rotary hammer drill".',       '/requests/detail.php?id=2',  0, '2026-08-12 14:05:00'),
(3,  4, 'request',   'New request on your listing',        'Dilan Fernando requested "Box of primary school storybooks".',          '/requests/detail.php?id=3',  0, '2026-08-13 18:20:00'),
(4,  6, 'accepted',  'Your request was accepted',          'Kasun Silva accepted your request for "Canon EF 50mm f/1.8 lens".',     '/requests/detail.php?id=4',  1, '2026-08-06 12:10:00'),
(5,  2, 'accepted',  'Your request was accepted',          'Dilan Fernando accepted your request for "Two-person camping tent".',   '/requests/detail.php?id=5',  1, '2026-08-08 17:00:00'),
(6,  5, 'accepted',  'Your request was accepted',          'Fathima Rizwan accepted your request for "Baby cot with mattress".',    '/requests/detail.php?id=6',  0, '2026-08-11 10:40:00'),
(7,  5, 'rejected',  'Your request was declined',          'Kasun Silva declined your request for "Canon EOS 90D camera body".',    '/requests/detail.php?id=7',  1, '2026-08-09 13:30:00'),
(8,  6, 'returned',  'Item marked as returned',            'Nimali Perera confirmed the return of "Folding aluminium ladder".',     '/requests/detail.php?id=11', 1, '2026-07-31 18:05:00'),
(9,  6, 'review',    'You received a review',              'Dilan Fernando left you a 5-star review.',                              '/profile/public.php?id=6',   0, '2026-08-05 10:00:00'),
(10, 3, 'review',    'You received a review',              'Fathima Rizwan left you a 5-star review.',                              '/profile/public.php?id=3',   0, '2026-08-04 09:15:00'),
(11, 1, 'complaint', 'New complaint filed',                'Dilan Fernando filed a complaint against Kasun Silva.',                 '/admin/complaints.php',      0, '2026-08-12 20:15:00'),
(12, 1, 'complaint', 'New complaint filed',                'Nimali Perera filed a complaint against Tharindu Bandara.',             '/admin/complaints.php',      0, '2026-08-14 07:45:00'),
(13, 2, 'broadcast', 'Scheduled maintenance this Sunday',  'NexUse will be briefly unavailable on Sunday morning for maintenance.', NULL,                         0, '2026-08-15 09:00:00'),
(14, 3, 'broadcast', 'Scheduled maintenance this Sunday',  'NexUse will be briefly unavailable on Sunday morning for maintenance.', NULL,                         0, '2026-08-15 09:00:00'),
(15, 4, 'broadcast', 'Scheduled maintenance this Sunday',  'NexUse will be briefly unavailable on Sunday morning for maintenance.', NULL,                         1, '2026-08-15 09:00:00'),
(16, 5, 'broadcast', 'Scheduled maintenance this Sunday',  'NexUse will be briefly unavailable on Sunday morning for maintenance.', NULL,                         0, '2026-08-15 09:00:00'),
(17, 6, 'broadcast', 'Scheduled maintenance this Sunday',  'NexUse will be briefly unavailable on Sunday morning for maintenance.', NULL,                         0, '2026-08-15 09:00:00');


-- ------------------------------------------------------- conversations ----
-- Two demo threads so the messaging feature is not empty on first load.
INSERT INTO conversations (conversation_id, listing_id, buyer_id, owner_id, created_at, last_message_at) VALUES
(1, 1, 5, 2, '2026-08-09 18:20:00', '2026-08-10 09:05:00'),
(2, 7, 6, 3, '2026-08-11 10:00:00', '2026-08-11 11:32:00');


-- ------------------------------------------------------------ messages ----
INSERT INTO messages (message_id, conversation_id, sender_id, body, is_read, created_at) VALUES
(1, 1, 5, 'Hello, is the laptop still available? Does the battery still hold a charge?', 1, '2026-08-09 18:20:00'),
(2, 1, 2, 'Yes, still here. About three hours on a full charge, less if you are on video calls.', 1, '2026-08-09 20:05:00'),
(3, 1, 5, 'That is fine for what I need. Could I collect it on Saturday morning?', 1, '2026-08-10 08:40:00'),
(4, 1, 2, 'Saturday works. Send a request through the site and I will accept it.', 0, '2026-08-10 09:05:00'),
(5, 2, 6, 'Is the 90D free for the weekend of the 22nd? I have a wedding shoot.', 1, '2026-08-11 10:00:00'),
(6, 2, 3, 'It is free that weekend. Two batteries and a 64GB card come with it.', 0, '2026-08-11 11:32:00');


SELECT
  (SELECT COUNT(*) FROM users)         AS users,
  (SELECT COUNT(*) FROM categories)    AS categories,
  (SELECT COUNT(*) FROM listings)      AS listings,
  (SELECT COUNT(*) FROM requests)      AS requests,
  (SELECT COUNT(*) FROM reviews)       AS reviews,
  (SELECT COUNT(*) FROM complaints)    AS complaints,
  (SELECT COUNT(*) FROM notifications) AS notifications,
  (SELECT COUNT(*) FROM conversations) AS conversations,
  (SELECT COUNT(*) FROM messages)      AS messages;

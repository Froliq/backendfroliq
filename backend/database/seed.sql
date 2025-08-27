-- Sample Data for Entertainment Platform Database
-- This file contains sample data for testing and development

USE entertainment_platform;

-- Sample Users
INSERT INTO users (first_name, last_name, email, phone, password_hash, date_of_birth, gender, is_verified) VALUES
('John', 'Doe', 'john.doe@example.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1990-05-15', 'male', TRUE),
('Jane', 'Smith', 'jane.smith@example.com', '9876543211', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1985-08-20', 'female', TRUE),
('Raj', 'Patel', 'raj.patel@example.com', '9876543212', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1992-12-10', 'male', TRUE),
('Priya', 'Sharma', 'priya.sharma@example.com', '9876543213', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1988-03-25', 'female', TRUE),
('Alex', 'Johnson', 'alex.johnson@example.com', '9876543214', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-07-08', 'other', TRUE);

-- Sample Venues
INSERT INTO venues (name, type, address, city, state, postal_code, phone, capacity, rating, total_ratings, latitude, longitude) VALUES
('PVR Cinemas Select City', 'cinema', 'A-3, District Centre, Saket', 'Delhi', 'Delhi', '110017', '011-40516666', 300, 4.2, 1250, 28.5245, 77.2066),
('INOX Nehru Place', 'cinema', 'Nehru Place, New Delhi', 'Delhi', 'Delhi', '110019', '011-40525252', 250, 4.0, 980, 28.5494, 77.2506),
('Kingdom of Dreams', 'theater', 'Auditorium Complex, Sector 29', 'Gurugram', 'Haryana', '122001', '0124-4564444', 850, 4.5, 2100, 28.4595, 77.0266),
('Phoenix MarketCity', 'cinema', 'LBS Marg, Kurla West', 'Mumbai', 'Maharashtra', '400070', '022-33063306', 400, 4.3, 1850, 19.0728, 72.8826),
('Hard Rock Cafe', 'restaurant', 'DLF CyberHub, Sector 29', 'Gurugram', 'Haryana', '122002', '0124-4982222', 200, 4.4, 1650, 28.4949, 77.0869),
('The Lalit', 'concert_hall', 'Barakhamba Avenue, Connaught Place', 'Delhi', 'Delhi', '110001', '011-42773377', 500, 4.6, 890, 28.6315, 77.2167),
('Jawaharlal Nehru Stadium', 'stadium', 'Gate 1, Lodhi Road', 'Delhi', 'Delhi', '110003', '011-24362592', 60000, 4.1, 3200, 28.5706, 77.2347);

-- Sample Movies
INSERT INTO movies (title, description, genre, language, duration, release_date, director, cast, rating, imdb_rating, poster_url, status) VALUES
('Spider-Man: Across the Spider-Verse', 'Miles Morales catapults across the Multiverse, where he encounters a team of Spider-People.', 'Animation,Action,Adventure', 'English', 140, '2023-06-02', 'Joaquim Dos Santos', 'Shameik Moore, Hailee Steinfeld, Brian Tyree Henry', 'UA', 8.7, '/images/spiderman-across.jpg', 'now_showing'),
('Pathaan', 'An action thriller about a RAW agent on a mission to stop a terrorist attack.', 'Action,Thriller', 'Hindi', 146, '2023-01-25', 'Siddharth Anand', 'Shah Rukh Khan, Deepika Padukone, John Abraham', 'UA', 7.2, '/images/pathaan.jpg', 'now_showing'),
('Scream VI', 'The survivors of the Ghostface killings leave Woodsboro behind for a fresh start in New York City.', 'Horror,Thriller', 'English', 123, '2023-03-10', 'Matt Bettinelli-Olpin', 'Melissa Barrera, Jenna Ortega, Jasmin Savoy Brown', 'A', 6.8, '/images/scream6.jpg', 'now_showing'),
('RRR', 'A fictional story of two legendary revolutionaries journey away from home before they began fighting for their country.', 'Action,Drama', 'Telugu', 187, '2022-03-25', 'S.S. Rajamouli', 'N.T. Rama Rao Jr., Ram Charan, Alia Bhatt', 'UA', 7.9, '/images/rrr.jpg', 'ended'),
('Avatar: The Way of Water', 'Jake Sully lives with his newfound family formed on the planet of Pandora.', 'Action,Adventure,Fantasy', 'English', 192, '2022-12-16', 'James Cameron', 'Sam Worthington, Zoe Saldana, Sigourney Weaver', 'UA', 7.6, '/images/avatar2.jpg', 'ended');

-- Sample Movie Showtimes
INSERT INTO movie_showtimes (movie_id, venue_id, show_date, show_time, screen_number, total_seats, available_seats, price) VALUES
(1, 1, '2025-08-28', '10:00:00', 'Screen 1', 100, 85, 250.00),
(1, 1, '2025-08-28', '13:30:00', 'Screen 1', 100, 92, 300.00),
(1, 1, '2025-08-28', '17:00:00', 'Screen 1', 100, 78, 350.00),
(1, 1, '2025-08-28', '20:30:00', 'Screen 1', 100, 65, 400.00),
(2, 2, '2025-08-28', '11:00:00', 'Screen 2', 80, 68, 220.00),
(2, 2, '2025-08-28', '14:30:00', 'Screen 2', 80, 72, 280.00),
(2, 2, '2025-08-28', '18:00:00', 'Screen 2', 80, 45, 320.00),
(3, 4, '2025-08-28', '21:00:00', 'Screen 5', 120, 95, 280.00),
(1, 4, '2025-08-29', '09:30:00', 'Screen 3', 150, 140, 240.00),
(2, 1, '2025-08-29', '16:00:00', 'Screen 2', 100, 88, 310.00);

-- Sample Artists
INSERT INTO artists (name, type, biography, rating, total_ratings) VALUES
('Arijit Singh', 'singer', 'One of the most popular playback singers in Bollywood, known for his soulful voice.', 4.8, 5200),
('Kapil Sharma', 'comedian', 'Popular Indian stand-up comedian and television personality.', 4.3, 3800),
('Sunidhi Chauhan', 'singer', 'Renowned Indian playback singer with a powerful voice.', 4.6, 2900),
('Zakir Khan', 'comedian', 'Stand-up comedian known for his relatable content and storytelling.', 4.5, 2100),
('The Local Train', 'band', 'Indian rock band known for their Hindi rock songs.', 4.4, 1650),
('Biswa Kalyan Rath', 'comedian', 'Stand-up comedian and writer, co-founder of AIB.', 4.2, 1800),
('Nucleya', 'singer', 'Electronic music producer and DJ, pioneer of Indian bass music.', 4.3, 1200);

-- Sample Events
INSERT INTO events (title, description, category, venue_id, artist_id, event_date, start_time, end_time, duration, total_capacity, available_tickets, min_price, max_price, poster_url, status, is_featured) VALUES
('Arijit Singh Live in Concert', 'Experience the magical voice of Arijit Singh performing his greatest hits live.', 'concert', 6, 1, '2025-09-15', '19:00:00', '22:00:00', 180, 500, 350, 1500.00, 8000.00, '/images/arijit-concert.jpg', 'upcoming', TRUE),
('Kapil Sharma Comedy Show', 'Get ready to laugh out loud with Kapil Sharma\'s hilarious stand-up comedy.', 'comedy', 3, 2, '2025-09-08', '20:00:00', '22:30:00', 150, 850, 620, 800.00, 3500.00, '/images/kapil-comedy.jpg', 'upcoming', TRUE),
('The Local Train Live', 'Rock night with The Local Train performing their popular Hindi rock songs.', 'concert', 5, 5, '2025-08-30', '21:00:00', '23:30:00', 150, 200, 85, 1200.00, 4000.00, '/images/local-train.jpg', 'upcoming', FALSE),
('Zakir Khan - Haq Se Single', 'Zakir Khan presents his new comedy special about being single.', 'comedy', 6, 4, '2025-09-22', '19:30:00', '21:30:00', 120, 500, 280, 600.00, 2500.00, '/images/zakir-comedy.jpg', 'upcoming', FALSE),
('Sunidhi Chauhan Musical Evening', 'An enchanting musical evening with the powerhouse vocalist Sunidhi Chauhan.', 'concert', 3, 3, '2025-10-05', '18:30:00', '21:00:00', 150, 850, 520, 1000.00, 5000.00, '/images/sunidhi-concert.jpg', 'upcoming', TRUE);

-- Sample Event Ticket Types
INSERT INTO event_ticket_types (event_id, type_name, price, total_quantity, available_quantity, description, benefits) VALUES
(1, 'VIP', 8000.00, 50, 25, 'VIP seating with premium amenities', '["Front row seating", "Meet & greet opportunity", "Complimentary refreshments", "VIP parking"]'),
(1, 'Premium', 3500.00, 150, 98, 'Premium seating with great view', '["Premium seating", "Priority entry", "Reserved parking"]'),
(1, 'General', 1500.00, 300, 227, 'General admission tickets', '["General seating", "Standard entry"]'),
(2, 'Gold', 3500.00, 100, 78, 'Gold category seating', '["Best seating", "Complimentary snacks", "VIP entry"]'),
(2, 'Silver', 1800.00, 300, 215, 'Silver category seating', '["Good seating", "Priority entry"]'),
(2, 'Bronze', 800.00, 450, 327, 'Bronze category seating', '["Standard seating", "General entry"]'),
(3, 'VIP', 4000.00, 20, 8, 'VIP experience', '["Front seating", "Backstage access", "Band merchandise"]'),
(3, 'General', 1200.00, 180, 77, 'General admission', '["Standing area", "Full show access"]');

-- Sample Restaurants
INSERT INTO restaurants (name, description, cuisine_type, address, city, state, phone, price_range, rating, total_ratings, features) VALUES
('The Spice Route', 'Authentic Indian cuisine with a modern twist, featuring dishes from across India.', 'Indian,North Indian,South Indian', 'Connaught Place, Inner Circle', 'Delhi', 'Delhi', '011-23417000', '$$$', 4.4, 2850, '["outdoor_seating", "wifi", "parking", "live_music", "bar"]'),
('Cafe Delhi Heights', 'Multi-cuisine restaurant known for its continental and Italian dishes.', 'Continental,Italian,American', 'Greater Kailash M Block Market', 'Delhi', 'Delhi', '011-29213949', '$$', 4.2, 1950, '["wifi", "parking", "outdoor_seating", "delivery"]'),
('Karim\'s', 'Historic Mughlai restaurant serving traditional dishes since 1913.', 'Mughlai,North Indian', 'Gali Kababian, Jama Masjid', 'Delhi', 'Delhi', '011-23264981', '$$', 4.1, 8500, '["takeaway", "traditional_seating", "historic"]'),
('Social', 'Trendy gastropub with a creative menu and vibrant atmosphere.', 'Continental,Asian,Italian', 'DLF CyberHub, Sector 29', 'Gurugram', 'Haryana', '0124-4982100', '$$', 4.3, 3200, '["wifi", "parking", "bar", "outdoor_seating", "live_music"]'),
('Indian Accent', 'Fine dining restaurant offering innovative Indian cuisine.', 'Indian,Contemporary', 'The Manor Hotel, Friends Colony', 'Delhi', 'Delhi', '011-43658888', '$$$$', 4.6, 1250, '["parking", "fine_dining", "bar", "private_dining"]'),
('Punjabi By Nature', 'Casual dining restaurant specializing in Punjabi cuisine.', 'Punjabi,North Indian', 'Rajouri Garden, Ring Road', 'Delhi', 'Delhi', '011-25416666', '$$', 4.0, 2100, '["parking", "family_friendly", "takeaway", "delivery"]');

-- Sample Restaurant Tables
INSERT INTO restaurant_tables (restaurant_id, table_number, capacity, location) VALUES
(1, 'T1', 2, 'indoor'), (1, 'T2', 4, 'indoor'), (1, 'T3', 6, 'indoor'), (1, 'T4', 2, 'outdoor'), (1, 'T5', 4, 'outdoor'),
(2, 'A1', 2, 'window'), (2, 'A2', 4, 'indoor'), (2, 'A3', 6, 'indoor'), (2, 'B1', 2, 'outdoor'), (2, 'B2', 8, 'indoor'),
(3, '1', 4, 'indoor'), (3, '2', 6, 'indoor'), (3, '3', 8, 'indoor'), (3, '4', 4, 'indoor'), (3, '5', 2, 'indoor'),
(4, 'S1', 2, 'indoor'), (4, 'S2', 4, 'indoor'), (4, 'S3', 6, 'outdoor'), (4, 'S4', 2, 'outdoor'), (4, 'L1', 10, 'indoor'),
(5, 'VIP1', 2, 'private'), (5, 'VIP2', 4, 'private'), (5, 'M1', 4, 'indoor'), (5, 'M2', 6, 'indoor'), (5, 'M3', 2, 'window'),
(6, 'F1', 4, 'indoor'), (6, 'F2', 6, 'indoor'), (6, 'F3', 8, 'indoor'), (6, 'F4', 2, 'indoor'), (6, 'F5', 4, 'outdoor');

-- Sample Bookings
INSERT INTO bookings (user_id, booking_type, booking_reference, movie_id, showtime_id, seats, event_id, ticket_type_id, quantity, restaurant_id, table_id, booking_date, booking_time, party_size, total_amount, booking_status, payment_status, customer_name, customer_email, customer_phone) VALUES
(1, 'movie', 'MOV2025001', 1, 1, '["A1", "A2"]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 500.00, 'confirmed', 'paid', 'John Doe', 'john.doe@example.com', '9876543210'),
(2, 'event', 'EVT2025001', NULL, NULL, NULL, 1, 3, 2, NULL, NULL, NULL, NULL, NULL, 3000.00, 'confirmed', 'paid', 'Jane Smith', 'jane.smith@example.com', '9876543211'),
(3, 'restaurant', 'RES2025001', NULL, NULL, NULL, NULL, NULL, NULL, 1, 2, '2025-08-30', '19:30:00', 4, 2500.00, 'confirmed', 'paid', 'Raj Patel', 'raj.patel@example.com', '9876543212'),
(4, 'movie', 'MOV2025002', 2, 5, '["B5", "B6", "B7"]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 660.00, 'confirmed', 'paid', 'Priya Sharma', 'priya.sharma@example.com', '9876543213'),
(5, 'event', 'EVT2025002', NULL, NULL, NULL, 2, 6, 3, NULL, NULL, NULL, NULL, NULL, 2400.00, 'pending', 'pending', 'Alex Johnson', 'alex.johnson@example.com', '9876543214');

-- Sample Reviews
INSERT INTO reviews (user_id, reviewable_type, reviewable_id, rating, title, comment, is_verified) VALUES
(1, 'movie', 1, 5, 'Amazing Animation!', 'Spider-Man: Across the Spider-Verse is a visual masterpiece with an engaging storyline.', TRUE),
(2, 'event', 1, 4, 'Great Concert', 'Arijit Singh\'s voice was mesmerizing. The venue and sound quality were excellent.', FALSE),
(3, 'restaurant', 1, 4, 'Delicious Food', 'The Spice Route offers authentic Indian flavors with great ambiance.', TRUE),
(4, 'movie', 2, 4, 'Entertaining', 'Pathaan is a thrilling action movie with great performances by Shah Rukh Khan.', TRUE),
(1, 'venue', 1, 4, 'Good Cinema', 'PVR Select City has comfortable seating and good sound quality.', TRUE),
(5, 'restaurant', 4, 5, 'Loved the Atmosphere', 'Social has a vibrant atmosphere with great food and drinks.', FALSE);

-- Sample Wishlists
INSERT INTO wishlists (user_id, item_type, item_id) VALUES
(1, 'event', 2), (1, 'restaurant', 5),
(2, 'movie', 3), (2, 'event', 4),
(3, 'restaurant', 2), (3, 'event', 1),
(4, 'movie', 1), (4, 'restaurant', 3),
(5, 'event', 3), (5, 'movie', 2);

-- Sample Coupons
INSERT INTO coupons (code, title, description, discount_type, discount_value, minimum_amount, maximum_discount, applicable_type, start_date, end_date) VALUES
('WELCOME20', 'Welcome Offer', 'Get 20% off on your first booking', 'percentage', 20.00, 500.00, 200.00, 'all', '2025-08-01', '2025-12-31'),
('MOVIE50', 'Movie Discount', 'Flat Rs.50 off on movie tickets', 'fixed', 50.00, 200.00, NULL, 'movie', '2025-08-15', '2025-09-15'),
('EVENT15', 'Event Special', '15% off on event tickets', 'percentage', 15.00, 1000.00, 500.00, 'event', '2025-08-20', '2025-10-20'),
('FOOD100', 'Restaurant Offer', 'Rs.100 off on restaurant bookings', 'fixed', 100.00, 800.00, NULL, 'restaurant', '2025-08-01', '2025-09-30');

-- Sample System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'EntertainMe', 'string', 'Website name', TRUE),
('site_currency', 'INR', 'string', 'Default currency', TRUE),
('booking_cancellation_hours', '24', 'number', 'Hours before event when cancellation is allowed', FALSE),
('max_tickets_per_booking', '10', 'number', 'Maximum tickets allowed per booking', TRUE),
('payment_gateway', 'razorpay', 'string', 'Default payment gateway', FALSE),
('customer_support_email', 'support@entertainme.com', 'string', 'Customer support email', TRUE),
('customer_support_phone', '1800-123-4567', 'string', 'Customer support phone', TRUE);

-- Sample Admin Users
INSERT INTO admin_users (username, email, password_hash, full_name, role, permissions) VALUES
('admin', 'admin@entertainme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', '["all"]'),
('manager', 'manager@entertainme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operations Manager', 'admin', '["bookings", "events", "movies", "restaurants", "users"]'),
('support', 'support@entertainme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Support Executive', 'moderator', '["bookings", "reviews", "contacts"]');

-- Sample Notifications
INSERT INTO notifications (user_id, title, message, type, related_type, related_id) VALUES
(1, 'Booking Confirmed', 'Your movie booking MOV2025001 has been confirmed for Spider-Man: Across the Spider-Verse.', 'booking', 'booking', 1),
(2, 'Event Reminder', 'Reminder: Your event "Arijit Singh Live in Concert" is tomorrow at 7:00 PM.', 'reminder', 'event', 1),
(3, 'Restaurant Booking Confirmed', 'Your table reservation at The Spice Route has been confirmed for August 30, 2025.', 'booking', 'booking', 3),
(4, 'New Movie Release', 'Check out the latest movies now showing at your nearest cinema!', 'promotion', 'movie', NULL),
(5, 'Payment Failed', 'Your payment for booking EVT2025002 has failed. Please try again.', 'booking', 'booking', 5);

-- Sample Payment Transactions
INSERT INTO payment_transactions (booking_id, transaction_id, gateway, amount, status, gateway_response, processed_at) VALUES
(1, 'TXN2025001', 'razorpay', 500.00, 'success', '{"payment_id": "pay_abc123", "status": "captured"}', '2025-08-27 14:30:00'),
(2, 'TXN2025002', 'razorpay', 3000.00, 'success', '{"payment_id": "pay_def456", "status": "captured"}', '2025-08-27 15:45:00'),
(3, 'TXN2025003', 'paytm', 2500.00, 'success', '{"txnId": "paytm789", "resultInfo": {"resultStatus": "TXN_SUCCESS"}}', '2025-08-27 16:20:00'),
(4, 'TXN2025004', 'razorpay', 660.00, 'success', '{"payment_id": "pay_ghi789", "status": "captured"}', '2025-08-27 17:10:00'),
(5, 'TXN2025005', 'razorpay', 2400.00, 'failed', '{"error": {"code": "BAD_REQUEST_ERROR", "description": "Payment failed"}}', '2025-08-27 18:00:00');

-- Sample Email Templates
INSERT INTO email_templates (template_name, subject, body, variables) VALUES
('booking_confirmation', 'Booking Confirmation - {{booking_reference}}', 
'Dear {{customer_name}},

Your booking has been confirmed!

Booking Details:
- Booking Reference: {{booking_reference}}
- Type: {{booking_type}}
- Amount: ₹{{total_amount}}
- Status: {{booking_status}}

{{booking_details}}

Thank you for choosing EntertainMe!

Best regards,
EntertainMe Team', 
'["customer_name", "booking_reference", "booking_type", "total_amount", "booking_status", "booking_details"]'),

('payment_success', 'Payment Successful - {{booking_reference}}', 
'Dear {{customer_name}},

Your payment has been successfully processed!

Payment Details:
- Transaction ID: {{transaction_id}}
- Amount: ₹{{amount}}
- Payment Method: {{payment_method}}
- Date: {{payment_date}}

Your booking is now confirmed.

Best regards,
EntertainMe Team', 
'["customer_name", "booking_reference", "transaction_id", "amount", "payment_method", "payment_date"]'),

('event_reminder', 'Event Reminder - {{event_title}}', 
'Dear {{customer_name}},

This is a reminder for your upcoming event:

Event: {{event_title}}
Date: {{event_date}}
Time: {{event_time}}
Venue: {{venue_name}}
Tickets: {{ticket_quantity}}

Please arrive at least 30 minutes before the event starts.

Enjoy the show!

EntertainMe Team', 
'["customer_name", "event_title", "event_date", "event_time", "venue_name", "ticket_quantity"]');

-- Sample FAQs
INSERT INTO faqs (question, answer, category, sort_order) VALUES
('How can I cancel my booking?', 'You can cancel your booking up to 24 hours before the event through your profile page or by contacting customer support.', 'bookings', 1),
('What payment methods do you accept?', 'We accept all major credit/debit cards, UPI, net banking, and digital wallets through our secure payment gateways.', 'payments', 2),
('Can I change my movie showtime after booking?', 'Yes, you can change your showtime subject to availability. Additional charges may apply if the new show has a higher ticket price.', 'movies', 3),
('How do I get my event tickets?', 'After successful payment, your e-tickets will be sent to your registered email. You can also download them from your profile.', 'events', 4),
('Is there a booking fee?', 'We charge a nominal convenience fee for online bookings, which will be displayed before payment.', 'general', 5),
('Can I book a table for the same day?', 'Yes, subject to availability. We recommend booking in advance to ensure your preferred time slot.', 'restaurants', 6),
('What if the event gets cancelled?', 'In case of event cancellation, you will receive a full refund within 5-7 business days.', 'events', 7),
('How can I contact customer support?', 'You can reach us at support@entertainme.com or call 1800-123-4567. Our support team is available 24/7.', 'general', 8);

-- Sample Contact Messages
INSERT INTO contact_messages (name, email, phone, subject, message, status) VALUES
('Rahul Kumar', 'rahul.kumar@email.com', '9876543215', 'Booking Issue', 'I am facing issues with my movie booking payment. Please help.', 'new'),
('Sneha Gupta', 'sneha.gupta@email.com', '9876543216', 'Event Inquiry', 'Can you please provide more details about the upcoming concerts in Delhi?', 'in_progress'),
('Amit Singh', 'amit.singh@email.com', '9876543217', 'Refund Request', 'I need to cancel my restaurant booking due to emergency. Please process refund.', 'resolved'),
('Pooja Mehta', 'pooja.mehta@email.com', '9876543218', 'Technical Issue', 'The website is not loading properly on my mobile device.', 'new'),
('Vikram Joshi', 'vikram.joshi@email.com', '9876543219', 'Feedback', 'Great service! I had an amazing experience booking through your platform.', 'closed');

-- Sample Activity Logs
INSERT INTO activity_logs (user_id, admin_id, action, table_name, record_id, ip_address, user_agent) VALUES
(1, NULL, 'CREATE', 'bookings', 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, NULL, 'CREATE', 'bookings', 2, '192.168.1.101', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X)'),
(NULL, 1, 'UPDATE', 'events', 1, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(3, NULL, 'CREATE', 'reviews', 3, '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'),
(NULL, 2, 'UPDATE', 'venues', 1, '192.168.1.11', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

-- Sample Coupon Usage
INSERT INTO coupon_usage (coupon_id, user_id, booking_id, discount_amount, used_at) VALUES
(1, 1, 1, 100.00, '2025-08-27 14:25:00'),
(2, 4, 4, 50.00, '2025-08-27 17:05:00'),
(3, 2, 2, 450.00, '2025-08-27 15:40:00');

-- Update some test data for realism
UPDATE movie_showtimes SET available_seats = available_seats - 2 WHERE id = 1; -- seats booked
UPDATE event_ticket_types SET available_quantity = available_quantity - 2 WHERE id = 3; -- tickets booked
UPDATE restaurant_tables SET is_available = FALSE WHERE id = 2; -- table booked

-- Update ratings based on reviews
UPDATE movies SET imdb_rating = (SELECT AVG(rating) FROM reviews WHERE reviewable_type = 'movie' AND reviewable_id = movies.id) WHERE id IN (SELECT DISTINCT reviewable_id FROM reviews WHERE reviewable_type = 'movie');
UPDATE restaurants SET rating = (SELECT AVG(rating) FROM reviews WHERE reviewable_type = 'restaurant' AND reviewable_id = restaurants.id), total_ratings = (SELECT COUNT(*) FROM reviews WHERE reviewable_type = 'restaurant' AND reviewable_id = restaurants.id) WHERE id IN (SELECT DISTINCT reviewable_id FROM reviews WHERE reviewable_type = 'restaurant');

-- Create indexes for better performance (additional to schema indexes)
CREATE INDEX idx_bookings_user_status ON bookings(user_id, booking_status);
CREATE INDEX idx_bookings_type_date ON bookings(booking_type, created_at);
CREATE INDEX idx_showtimes_date_venue ON movie_showtimes(show_date, venue_id);
CREATE INDEX idx_events_date_category ON events(event_date, category);
CREATE INDEX idx_reviews_rating_verified ON reviews(rating, is_verified);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- Add some triggers for data consistency
DELIMITER $

-- Trigger to update available seats after booking
CREATE TRIGGER update_seats_after_booking 
AFTER INSERT ON bookings 
FOR EACH ROW 
BEGIN
    IF NEW.booking_type = 'movie' AND NEW.showtime_id IS NOT NULL THEN
        UPDATE movie_showtimes 
        SET available_seats = available_seats - JSON_LENGTH(NEW.seats)
        WHERE id = NEW.showtime_id;
    END IF;
    
    IF NEW.booking_type = 'event' AND NEW.ticket_type_id IS NOT NULL THEN
        UPDATE event_ticket_types 
        SET available_quantity = available_quantity - NEW.quantity
        WHERE id = NEW.ticket_type_id;
    END IF;
END$

-- Trigger to update table availability after restaurant booking
CREATE TRIGGER update_table_after_booking 
AFTER INSERT ON bookings 
FOR EACH ROW 
BEGIN
    IF NEW.booking_type = 'restaurant' AND NEW.table_id IS NOT NULL THEN
        UPDATE restaurant_tables 
        SET is_available = FALSE 
        WHERE id = NEW.table_id;
    END IF;
END$

-- Trigger to increment coupon usage
CREATE TRIGGER update_coupon_usage 
AFTER INSERT ON coupon_usage 
FOR EACH ROW 
BEGIN
    UPDATE coupons 
    SET used_count = used_count + 1 
    WHERE id = NEW.coupon_id;
END$

DELIMITER ;

COMMIT;
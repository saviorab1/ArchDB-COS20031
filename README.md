# Archery Score Recording System (ArchDB)

## Database Setup Guide

### Overview
This project implements a comprehensive database schema for recording archery competition scores using MariaDB. The schema is optimized for the MVP implementation plan.

### Key Files
- **Query.md** - Complete SQL queries for database creation and setup
- **DB for Archery Score Recording.md** - Requirements specification  
- **IMPLEMENTATION_PLAN.md** - Detailed implementation roadmap

### Quick Start

1. Create database (from Query.md): CREATE DATABASE archery_score_db
2. Create all tables in order (round, archer, round_range, competition, registration, score, equivalent_round)
3. Load sample data for testing
4. Create database user: CREATE USER archery_user

### Database Tables

Core MVP Tables:
- archer: Archer profiles with personal details and equipment
- round: Round definitions (WA 900, WA 720, etc.)
- round_range: Structure of each round (distances, ends, target faces)
- competition: Competition events
- registration: Links archers to competitions
- score: Individual scores with JSON arrow data

Optional Table:
- equivalent_round: Equivalent rounds for different categories

### Key Design Decisions

✓ Arrow scores stored as JSON array [10,9,8,7,10,9]
✓ Frontend handles all business logic (filtering, sorting, categories)
✓ Minimal database schema for MVP
✓ 21 indexes for performance optimization
✓ Cascading deletes for data integrity
✓ UTF8MB4 character encoding

### Sample Queries

Query.md includes 10 useful query examples:
- Get archer with age calculation
- Find personal best for round
- Get competition results with rankings
- Filter scores by date range
- Multi-filter queries with optional parameters

### Frontend Integration

Frontend handles:
- Category calculation from age and gender
- Score totals calculation
- Filtering, sorting, validation
- All business logic

Backend is purely a data layer.

### Troubleshooting

- Foreign key error: Create tables in correct order
- User access denied: Run GRANT and FLUSH PRIVILEGES
- JSON not available: Update MariaDB to 10.2.7+

See Query.md for complete documentation and SQL scripts.

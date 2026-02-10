# Content Management System Setup

## Quick Start Guide

### Step 1: Setup Database
Run this URL in your browser to create the database table and populate it with default images:
```
http://localhost/Constructa/backend/setup_gallery_images.php
```

This will:
- Create the `gallery_images` table
- Populate it with all the existing images from explore_designs.php
- Show a success message

### Step 2: Access Content Management
Navigate to:
```
http://localhost/Constructa/content.php
```

## Features

### Content Management Page (`content.php`)
- **View all images** in the gallery with statistics
- **Filter by category** (All, Exteriors, Interiors)
- **Add new images** with title, description, category
- **Delete images** with confirmation
- **Live statistics** showing total images, exteriors, and interiors
- **Preview gallery** button to see changes in explore_designs.php

### Gallery Page (`explore_designs.php`)
- **Dynamically loads images** from the database
- **Reflects changes** made in content.php immediately
- All existing features (filtering, modal, download) still work

## How to Use

### Adding Images
1. Click "Add New Image" button
2. Enter image URL (from Pexels, Unsplash, or any other source)
3. Select category (Exterior or Interior)
4. Add subcategory (optional, e.g., "living_room", "kitchen")
5. Enter title and description
6. Click "Add Image"

### Deleting Images
1. Find the image card
2. Click the red trash icon
3. Confirm deletion
4. Image is removed from both content.php and explore_designs.php

### Viewing Changes
- Click "Preview Gallery" button to open explore_designs.php in a new tab
- Refresh explore_designs.php to see the latest changes

## Database Structure

Table: `gallery_images`
- `id` - Auto-increment primary key
- `image_url` - Full URL to the image
- `category` - 'exterior' or 'interior'
- `subcategory` - Optional (e.g., 'living_room', 'kitchen', 'bedroom')
- `title` - Image title
- `description` - Image description
- `created_at` - Timestamp

## Navigation

From the "Content & Plans" menu card in homeowner.php, clicking will redirect to content.php where you can manage all gallery images.

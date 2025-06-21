# Above the Fold Tracker (WordPress Plugin)

![Plugin Banner](assets/banner-1544x500.png)  
*(You can customize this banner if you wish)*

## Born from a Real Need

This plugin wasn't built for show, or as a throwaway coding exercise. It was born from a simple but persistent question: What do users really see first? And more importantly, what do they in those first seconds?

The area above the fold, that first visible screen before scrolling, is often where the decision happens to engage or to bounce. Above the Fold Tracker was created to track those critical interactions in that very moment. It records which links are actually visible, captures the ones that get clicked, stores them with screen size and timestamp, and sends everything cleanly through a REST API. The codebase is simple, testable, and ready to grow.

## Core Features

- Detects and tracks clicks on links visible on page load  
- Records detailed context: URL, screen resolution, timestamp  
- Automatically purges old data to keep things light  
- REST API endpoint for structured data submission  
- Admin analysis dashboard (coming soon – in progress)

## Installation

### Standard WordPress method

1. Download the latest release from GitHub  
2. Go to **Plugins > Add New > Upload Plugin**  
3. Select the `.zip` file  
4. Activate "Above the Fold Tracker"

### Developer-friendly way

```bash
cd wp-content/plugins
git clone https://github.com/kilowe/above-the-fold-tracke 
composer install --no-dev
```

## How it Works

Once activated, the plugin silently creates the necessary database table and begins collecting data. No extra configuration needed. It immediately starts tracking link visibility and user clicks in the upper section of each page.

### API Access

You can retrieve the data via a clean API:

**Endpoint:**  
`POST /wp-json/abovefold/v1/track`

**Example payload:**

```json
{
  "screen": "1920x1080",
  "links": [
    {"url": "https://example.com/link1"},
    {"url": "https://example.com/link2"}
  ]
}
```

## Frontend Integration (Optional)

Here’s a sample script to track links if you want to manually wire it into your theme or head scripts:

```javascript
document.addEventListener('DOMContentLoaded', () => {
  const visibleLinks = document.querySelectorAll('a:not([href^="#"])');

  visibleLinks.forEach(link => {
    link.addEventListener('click', trackClick);
  });

  function trackClick() {
    const screen = `${window.screen.width}x${window.screen.height}`;
    const links = Array.from(visibleLinks).map(l => ({ url: l.href }));

    fetch('/wp-json/abovefold/v1/track', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': atfAdmin.nonce
      },
      body: JSON.stringify({ screen, links })
    });
  }
});
```

This script only runs for non logged in users to ensure clean data.

## Quick Testing with `[atf_test]` Shortcode

If you prefer not to touch the frontend yet, or want to validate quickly that the plugin works, you can use the built-in `[atf_test]` shortcode.

### What It Does

This shortcode renders a button inside a WordPress page that, when clicked, simulates an AJAX tracking event using sample data. It’s especially useful for developers, testers, and reviewers to confirm plugin functionality without needing a full frontend setup.

### How to Use It

1. Create a new WordPress page.
2. In the page content, insert:
   ```
   [atf_test]
   ```
3. Visit the page while logged in as an administrator.
4. Click the "Run ATF Test" button.
5. You'll see a success or error message with results from the AJAX call.

### What Happens Behind the Scenes

- The button triggers an AJAX request to the tracking endpoint.
- It sends two dummy links and a screen resolution of 1920x1080.
- The result is displayed directly on the page.

### Where It's Defined

You’ll find the shortcode code in:

```php
src/AdminDashboard.php
```

Inside this method:

```php
AdminDashboard::add_test_shortcode();
```

Make sure this method is called inside your `init()` method in `Plugin.php`.

### Debugging Tips

If the shortcode doesn’t seem to work:

- Make sure you’re logged in as an admin
- Disable any caching plugin
- Use this SQL to verify the post content:
  ```sql
  SELECT * FROM wp_posts WHERE post_content LIKE '%[atf_test]%';
  ```

## Customization

### Hooks and Filters

You can fine-tune or extend behavior like so:

```php
// Modify data before saving
add_filter('abovefold_tracking_data', function($data) {
    $data['extra'] = 'custom value';
    return $data;
});

// React to data being recorded
add_action('abovefold_data_recorded', function($insert_id, $data) {
    // Your custom logic here
}, 10, 2);
```

### Config Options (in `wp-config.php`)

```php
define('ABOVE_FOLD_DATA_RETENTION', 15); // Default is 7 days
define('ABOVE_FOLD_PURGE_LIMIT', 500);   // Default is 1000 records
```

## Contributing

This plugin was made to be shared, questioned, improved.

1. Fork the repo  
2. Create a feature branch (`git checkout -b feature/my-feature`)  
3. Commit your work (`git commit -am 'Add cool feature'`)  
4. Push your branch (`git push origin feature/my-feature`)  
5. Open a pull request

### Stack

- PHP 8.x recommended  
- Composer for dependency management  
- PHPUnit & Brain Monkey (optional, in test phase)  
- Node.js if working on frontend assets

## License

Distributed under the [GPLv2 or later](LICENSE). Fork it, hack it, build on top of it.

## Final Notes

This project is still growing. The foundations are solid, the purpose is clear, and every file here was written with care. If you’ve ever built something because it had to exist, you’ll understand the spirit behind this.

---

**Version**: 1.0.0  
**Last update**: June 21, 2025  
**Tested on**: WordPress 6.5+, PHP 7.4+  
**Contact**: [kankuc94@gmail.com]  
**Issue tracker**: [GitHub Issues](https://github.com/kilowe/above-the-fold-tracke)

from playwright.sync_api import sync_playwright, expect

def test_mobile_menu_closes(page):
    # 1. Arrange
    # Modified to point to landing.php
    page.goto("http://localhost:8000/landing.php")
    page.set_viewport_size({"width": 375, "height": 667})

    # 2. Open menu
    # Using a robust selector for the toggle button
    toggle_btn = page.locator("button:has(i.fa-bars)")
    toggle_btn.click()

    mobile_menu = page.locator("#mobile-menu")
    expect(mobile_menu).to_be_visible()

    # 3. Act: Click a link
    fitur_link = mobile_menu.get_by_role("link", name="Fitur")
    fitur_link.click()

    # 4. Assert: Menu should close
    # This assertion will FAIL before the fix.
    expect(mobile_menu).to_be_hidden(timeout=2000)

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            test_mobile_menu_closes(page)
            print("Test passed: Mobile menu closed after click.")
        except Exception as e:
            print(f"Test failed: {e}")
        finally:
            browser.close()

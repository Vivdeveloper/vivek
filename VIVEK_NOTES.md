# 📝 Simple CMS Notes (VivFramework)

This guide explains how to use the CMS functionality in plain English. No complex code, just what you can do!

### 🌍 Links and URLs
- **Creating Links:** If you need a link to a page or a blog post, there is a way to get the exact web address based on its URL "slug".
- **Safety Rule:** Every piece of text coming from the database should be "cleaned" before it is shown on the screen. This stops hackers from breaking your website.

### 📄 Pages and Blog Articles
- **Fetch Specific Page:** You can pull all the information from any page (like "Home" or "About Us") just by using its URL name. This gives you the Title, Content, and Image.
- **Latest Blog Posts:** You can ask the system to give you a list of the most recent blog articles to show in a sidebar or at the bottom of a page.
- **Search:** The system has a built-in search tool that looks for your keywords inside post titles and content.

### 🧭 Navigation and Sidebars
- **Navigation Menus:** The menus you build in your Admin Panel are stored as a "tree". You can tell the system to look through this tree and list all the links automatically.
- **Global Settings:** Any setting you save in the "Theme Settings" area (like your Site Logo, Title, or Tagline) can be pulled into your header or footer easily.

### 🛡️ Admin Security (Mandatory)
- **Restricting Access:** Only Admins should be able to see certain files. The system checks if you are logged in and if you have the "Admin" role before showing sensitive pages.
- **Form Protection:** Every form where you type information (like adding a post) has a hidden protection tag. This makes sure that nobody else can submit a form on your behalf.
- **User Info:** You can always check who is currently logged in, what their name is, and what their email address is.

---
**Summary Hint:** 
- To show a page: Use the Page Fetch function.
- To show a link: Use the URL function.
- To keep it safe: Use the Cleaning function for all text.

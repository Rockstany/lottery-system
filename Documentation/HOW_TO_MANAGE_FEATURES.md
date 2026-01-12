# 🎯 How to Enable/Disable Features for Communities

## Quick Answer

**As an Admin, you enable/disable features in the Feature Management page.**

### Step-by-Step Guide:

1. **Login as Admin**
   - Go to: `/public/login.php`
   - Enter your admin credentials

2. **Navigate to Feature Management** (3 ways):

   **Option A - Quick Actions Button:**
   - From Admin Dashboard
   - Click the purple **"⚙️ Manage Features"** button in the "Quick Actions" section
   - This will open the Feature Management page

   **Option B - Community List:**
   - From Admin Dashboard
   - Scroll down to **"Feature Management by Community"** section
   - Find the community you want to manage
   - Click **"⚙️ Manage Features"** button for that community

   **Option C - Direct URL:**
   - Navigate to: `/public/admin/community-features.php?community_id=1`
   - (Replace `1` with the community ID you want to manage)

3. **Select Community**
   - At the top of the Feature Management page, you'll see a dropdown
   - Select the community you want to manage features for
   - The page will reload showing features for that community

4. **Enable or Disable Features**
   - You'll see feature cards for each available feature
   - **Green border** = Feature is ENABLED
   - **Gray border** = Feature is DISABLED
   - Click the button to toggle:
     - **"✓ Enable Feature"** button (green) - to enable
     - **"🚫 Disable Feature"** button (red) - to disable
   - Confirm your action in the popup dialog

5. **Verify Changes**
   - Success message will appear at the top
   - Feature card border will change color
   - Status will update (Enabled/Disabled with date)

---

## What Happens When You Enable/Disable?

### When You ENABLE a Feature:
✅ The feature appears as a card on the Group Admin's dashboard
✅ Group Admin can click and access the feature
✅ Status shows as "Enabled" with the date
✅ Feature card has green border

### When You DISABLE a Feature:
❌ The feature is removed from Group Admin's dashboard
❌ Group Admin cannot access the feature
❌ Status shows as "Disabled"
❌ Feature card has gray border

---

## Screenshots/Visual Guide

### Admin Dashboard - Access Points

```
┌─────────────────────────────────────────────────────────────┐
│                    ADMIN DASHBOARD                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Quick Actions                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │   👤     │  │   🏘️     │  │   👥     │  │   ⚙️     │  │
│  │  Create  │  │  Manage  │  │  View    │  │  Manage  │  │ ← CLICK HERE
│  │  Group   │  │ Commun.  │  │  Users   │  │ Features │  │
│  │  Admin   │  │          │  │          │  │          │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
│                                                             │
│  Feature Management by Community                            │
│  ┌─────────────────────────────────────────────────────┐  │
│  │ Springfield Community      [⚙️ Manage Features]     │  │ ← OR CLICK HERE
│  │ Shelbyville Community      [⚙️ Manage Features]     │  │
│  │ Capital City Community     [⚙️ Manage Features]     │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Feature Management Page

```
┌─────────────────────────────────────────────────────────────┐
│              ⚙️ FEATURE MANAGEMENT                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Select Community to Manage:                                │
│  ┌────────────────────────────────────────────────┐        │
│  │ Springfield Community                      ▼   │        │
│  └────────────────────────────────────────────────┘        │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐  │
│  │  🎟️  Lottery System      lottery_system            │  │
│  │                                                     │  │
│  │  Complete lottery management system                │  │
│  │                                                     │  │
│  │  ✓ Enabled (Jan 12, 2026)                          │  │ ← STATUS
│  │                                                     │  │
│  │  [ 🚫 Disable Feature ]                            │  │ ← CLICK TO DISABLE
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐  │
│  │  🎯  Event Management    event_system               │  │
│  │                                                     │  │
│  │  Manage community events and activities            │  │
│  │                                                     │  │
│  │  ○ Disabled                                         │  │ ← STATUS
│  │                                                     │  │
│  │  [ ✓ Enable Feature ]                              │  │ ← CLICK TO ENABLE
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Group Admin Dashboard - What They See

**When Feature is ENABLED:**
```
┌─────────────────────────────────────────────────────────────┐
│          GROUP ADMIN DASHBOARD                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Available Features                                         │
│                                                             │
│  ┌──────────────────────────┐  ┌──────────────────────┐   │
│  │         🎟️               │  │        🎯            │   │
│  │    Lottery System        │  │  Event Management    │   │
│  │                          │  │                      │   │
│  │  Complete lottery        │  │  Manage community    │   │
│  │  management system       │  │  events              │   │
│  │                          │  │                      │   │
│  │  [Access Feature]        │  │  [Access Feature]    │   │
│  └──────────────────────────┘  └──────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**When NO Features are ENABLED:**
```
┌─────────────────────────────────────────────────────────────┐
│          GROUP ADMIN DASHBOARD                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Available Features                                         │
│                                                             │
│              📦                                             │
│                                                             │
│        No Features Enabled                                  │
│                                                             │
│  Your administrator hasn't enabled any features             │
│  for your community yet.                                    │
│                                                             │
│  Please contact your administrator to get started.          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Important URLs

| Page | URL | Purpose |
|------|-----|---------|
| Admin Dashboard | `/public/admin/dashboard.php` | Main admin page with links to feature management |
| Feature Management | `/public/admin/community-features.php?community_id=1` | Enable/disable features for a specific community |
| Group Admin Dashboard | `/public/group-admin/dashboard.php` | Where Group Admins see enabled features |

---

## Common Questions

### Q: Where do I enable features?
**A:** Admin Dashboard → Click "⚙️ Manage Features" button → Select community → Toggle features

### Q: Why is the UI so plain?
**A:** The UI has been completely redesigned! The new Feature Management page has:
- Beautiful gradient header
- Card-based layout for each feature
- Color-coded status (green border = enabled, gray = disabled)
- Smooth animations and hover effects
- Community selector dropdown
- Success/error message alerts
- Professional typography and spacing

### Q: Can I enable features for multiple communities at once?
**A:** No, you must enable features one community at a time. Use the dropdown at the top of the Feature Management page to switch between communities.

### Q: How do I know if a feature is enabled?
**A:** Look for:
- ✅ Green border around the feature card
- "✓ Enabled" status badge (green background)
- Date when it was enabled
- Red "🚫 Disable Feature" button

### Q: What happens immediately after I enable a feature?
**A:**
1. Success message appears at the top
2. Feature card border turns green
3. Status changes to "✓ Enabled (date)"
4. Button changes to red "🚫 Disable Feature"
5. Group Admin will see the feature card on their dashboard

### Q: Do I need to refresh the Group Admin page?
**A:** Yes, the Group Admin needs to refresh their dashboard to see newly enabled features appear.

---

## Troubleshooting

### Issue: "Manage Features" button not visible on Admin Dashboard
**Solution:** Make sure you're logged in as Admin (not Group Admin). Check the top right of the dashboard - it should say "System Administrator"

### Issue: Features not appearing on Group Admin dashboard
**Solution:**
1. Verify the feature is enabled (green border) in Feature Management
2. Make sure you're managing the correct community
3. Have the Group Admin refresh their dashboard page
4. Check that the Group Admin is assigned to the correct community

### Issue: Can't find the Feature Management page
**Solution:** Use these direct paths:
- Admin Dashboard: `/public/admin/dashboard.php`
- Feature Management: `/public/admin/community-features.php?community_id=1`

### Issue: Changes not saving
**Solution:**
1. Check that you clicked the Enable/Disable button
2. Confirm the action in the popup dialog
3. Look for the success message at the top of the page
4. If no message appears, check your browser console for errors

---

## UI Design Features

The new Feature Management page includes:

✨ **Modern Design Elements:**
- Gradient purple header matching the brand
- Card-based layout with shadows and hover effects
- Color-coded borders (green = enabled, gray = disabled)
- Large, clear feature icons
- Professional typography
- Responsive grid layout

✨ **User Experience:**
- Community selector dropdown at the top
- Clear status indicators with icons
- Confirmation dialogs before toggling
- Success/error message alerts
- Smooth animations
- Back to Dashboard link
- Mobile-responsive design

✨ **Information Display:**
- Feature name and icon
- Feature key (technical identifier)
- Feature description
- Current status (Enabled/Disabled)
- Date when enabled
- Large, clear action buttons

---

## Summary

**To enable/disable features:**
1. Login as Admin
2. Go to Admin Dashboard
3. Click "⚙️ Manage Features" (purple button)
4. Select community from dropdown
5. Click Enable or Disable button on feature card
6. Confirm the action

**That's it!** The UI is now modern, beautiful, and easy to use. 🎉

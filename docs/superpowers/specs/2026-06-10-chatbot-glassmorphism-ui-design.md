# Chatbot Glassmorphism UI - Design Spec

## Overview
Modernize the CI4 HMVC chatbot UI with a glassmorphism design, adding conversation management, suggestion chips, markdown rendering, and rich interactions.

## Layout
- Two-panel: collapsible sidebar (conversation list) + main chat area
- Animated gradient mesh background
- Glass-style panels with `backdrop-filter: blur(20px)` and semi-transparent backgrounds

## Elements

### Sidebar
- "New Chat" button at top
- Session-based conversation list (fetched via AJAX from existing session)
- Active conversation highlighted, delete on hover
- Collapsible on mobile with hamburger toggle

### Header
- Bot avatar (robot icon in glass circle)
- "AI ChatBot" title with status dot (green/red for Ollama availability)
- Dark/light mode toggle icon button

### Welcome/Empty State
- Large bot avatar with greeting text
- Grid of 6 suggestion chips (glass-style, clickable):
  - "Tell me a joke"
  - "Write a poem"
  - "Explain quantum computing"
  - "Give me productivity tips"
  - "Help me code"
  - "What's the weather?"

### Chat Area
- Messages slide in with fade-up animation
- User bubbles: primary gradient (purple-blue), right-aligned
- Bot bubbles: glass white (light mode) / glass dark (dark mode), left-aligned
- Bot messages rendered with simple markdown (bold, code blocks, inline code, lists, links)
- Copy button (icon) appears on hover over bot messages
- Relative timestamps shown on message hover
- Avatar icons next to messages (user icon / bot icon)

### Typing Indicator
- Glass-style animated dots below the last bot message

### Input Area
- Auto-resizing textarea (single line expands to max 4 lines)
- Send button (paper plane icon) with glass style
- Row of suggestion chips above input (persistent)

### Scroll-to-Bottom Button
- Floating glass button, appears when scrolled up more than 300px

## Color Palette
- Background: Animated gradient mesh (#667eea, #764ba2, #f093fb)
- Glass surfaces: rgba(255,255,255,0.15) light / rgba(0,0,0,0.25) dark
- User bubble: linear-gradient(135deg, #667eea, #764ba2)
- Bot bubble: glass white with border
- Text: #1a1a2e / rgba(255,255,255,0.9)
- Status dot: #22c55e (online) / #ef4444 (offline)
- Border: rgba(255,255,255,0.2)

## Interactions
- Message bubbles: fade-up + scale animation on append
- Sidebar: slide in/out on mobile
- Suggestion chips: scale + glow on hover
- Dark mode: smooth transition, persisted in localStorage
- Copy button: "Copied!" toast feedback
- Timestamps: shown on hover with `title` attribute

## Technical Notes
- All styles self-contained in the view (no external CSS files beyond Tailwind)
- Simple markdown parser using regex replacements (no external library)
- Session-based conversation management (existing backend unchanged)
- AJAX endpoints unchanged (`GET /chatbot`, `POST /chatbot/send`)
- Ollama status checked via existing `isAvailable()` method

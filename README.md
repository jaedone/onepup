# OnePUP  
### A Centralized Resource Platform for PUP Students  

> Making essential academic resources, organizations, and communities accessible in one place.

---

## 🌐 Live Demo  
👉 https://onepup.up.railway.app/

---

## 📌 Overview  
**OnePUP** is a centralized web platform designed for students of the Polytechnic University of the Philippines (PUP) to discover and access essential resources — including academic files, official websites, student organizations, and online communities.

It solves the fragmentation of student resources across platforms like Facebook, Google Drive, and Discord by aggregating them into a single, searchable interface.

---

## 🧠 Problem  

Students rely on scattered, informal sources for academic and organizational information, leading to:
- Inefficient discovery  
- Information gaps  
- Loss of community knowledge  

**OnePUP** centralizes and structures this data into a searchable system.

---

## 🚀 Key Features  

### 🔍 Smart Search System  
- Ranked results:
  - Exact matches (title-based)  
  - Partial matches  
  - Description-based matches  
- Debounced input for performance optimization  
- Designed for scalable fuzzy search (Fuse.js-ready)

---

### 🧭 Dynamic Navigation  
- Sticky navbar with active section highlighting  
- Smooth scrolling with offset handling  
- IntersectionObserver-based tracking   

---

### 🗂️ Resource Aggregation  
Categories:
- 📁 Files  
- 🌐 Websites  
- 📘 Facebook Pages  
- 👥 Communities  

Labeling system:
- **Official** (Maroon)  
- **Unofficial** (Gold)  

---

### 📝 Contribution System  
- Users can submit resources or report issues
- Input validation and structured handling  
- Extendable for moderation workflows  

---

## 🏗️ Tech Stack  

**Frontend**
- Blade  
- Tailwind CSS  
- Vite  

**Backend**
- Laravel 12  
- Livewire 4  

**Database**
- PostgreSQL (Supabase)  

---

## ⚙️ Engineering Highlights  

- Search ranking prioritizing relevance over simple matching  
- Scalable schema for multiple resource types  
- Clean separation of UI, logic, and data layers  
- UX-driven engineering decisions (navigation, responsiveness)  

---

## 🔮 Future Work  

- Organization Recommendation System  
- Personalization (saved resources, user signals)  
- Moderation & verification system
- AI Chatbot about PUP Resources

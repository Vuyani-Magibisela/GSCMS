<div align="left" style="position: relative;">
<img src="https://raw.githubusercontent.com/PKief/vscode-material-icon-theme/ec559a9f6bfd399b82bb44393651661b08aaf7ba/icons/folder-markdown-open.svg" align="right" width="30%" style="margin: -20px 0 0 20px;">
<h1>GDE SciBOTICS Competition Management System (GSCMS)</h1>
<p align="left">
	<em>A comprehensive PHP-based MVC web application for managing science competitions, built for the Gauteng Department of Education SciBOTICS program.</em>
</p>
<p align="left">
	<img src="https://img.shields.io/github/license/Vuyani-Magibisela/GSCMS.git?style=flat&logo=opensourceinitiative&logoColor=white&color=0080ff" alt="license">
	<img src="https://img.shields.io/github/last-commit/Vuyani-Magibisela/GSCMS.git?style=flat&logo=git&logoColor=white&color=0080ff" alt="last-commit">
	<img src="https://img.shields.io/github/languages/top/Vuyani-Magibisela/GSCMS.git?style=flat&color=0080ff" alt="repo-top-language">
	<img src="https://img.shields.io/github/languages/count/Vuyani-Magibisela/GSCMS.git?style=flat&color=0080ff" alt="repo-language-count">
</p>
<p align="left">Built with the tools and technologies:</p>
<p align="left">
	<img src="https://img.shields.io/badge/Composer-885630.svg?style=flat&logo=Composer&logoColor=white" alt="Composer">
	<img src="https://img.shields.io/badge/JavaScript-F7DF1E.svg?style=flat&logo=JavaScript&logoColor=black" alt="JavaScript">
	<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat&logo=PHP&logoColor=white" alt="PHP">
</p>
</div>
<br clear="right">

##  Table of Contents

- [ Overview](#-overview)
- [ Features](#-features)
- [ Project Structure](#-project-structure)
  - [ Project Index](#-project-index)
- [ Getting Started](#-getting-started)
  - [ Prerequisites](#-prerequisites)
  - [ Installation](#-installation)
  - [ Usage](#-usage)
  - [ Testing](#-testing)
- [ Project Roadmap](#-project-roadmap)
- [ Contributing](#-contributing)
- [ License](#-license)
- [ Acknowledgments](#-acknowledgments)

---

##  Overview

The **GDE SciBOTICS Competition Management System (GSCMS)** is a robust, full-featured web application designed to streamline the management of science competitions for the Gauteng Department of Education. Built using a custom PHP MVC framework, the system handles everything from user registration and team management to judging workflows and competition logistics.

### Key Capabilities
- **Multi-role User Management**: Admin, School Coordinators, Team Coaches, and Judges
- **Competition Management**: Full lifecycle from setup to results
- **Team & Participant Registration**: Streamlined registration with document management
- **Judging System**: Comprehensive scoring and evaluation workflows
- **Resource Management**: Venue logistics, inventory, and resource allocation
- **Automated Communications**: Email notifications and updates
- **Reporting & Analytics**: Detailed competition reports and insights

---

##  Features

### 🔐 **Authentication & Authorization**
- Session-based authentication with role-based access control
- Password reset functionality with email verification
- Multi-level user permissions (Admin, School Coordinator, Team Coach, Judge)
- Account lockout protection and rate limiting

### 🏫 **School & Team Management**
- School registration and coordinator assignment
- Team creation and participant management
- Consent form handling and document uploads
- Team status tracking and approval workflows

### 🏆 **Competition Management**
- Multi-phase competition setup (Regional, Provincial, National)
- Category-based competition organization
- Schedule management and venue logistics
- Real-time competition status updates

### ⚖️ **Judging System**
- Customizable scoring rubrics
- Judge assignment and scoring workflows
- Score validation and conflict resolution
- Automated score calculations and rankings

### 📊 **Reporting & Analytics**
- Comprehensive competition reports
- Participant statistics and demographics
- School performance analytics
- Export capabilities for various formats

### 📧 **Communication System**
- Automated email notifications
- Announcement broadcasting
- Registration confirmations
- Competition updates and reminders

### 🔧 **Administrative Tools**
- Database migration and seeding system
- User management and role assignment
- System configuration and settings
- Backup and maintenance utilities

### 🏗️ **Architecture & Technology**

**Framework & Architecture:**
- **Custom PHP MVC Framework**: Clean separation of concerns with custom routing
- **Active Record Pattern**: Simplified database interactions through BaseModel
- **Dependency Injection**: Service container for flexible component management
- **Middleware Support**: Authentication, CSRF protection, and rate limiting

**Database & Storage:**
- **MySQL/MariaDB**: Relational database with foreign key constraints
- **Migration System**: Version-controlled schema changes with rollback support
- **Seeding System**: Environment-specific data population (development/production)
- **Connection Pooling**: Efficient database connection management

**Security Features:**
- **CSRF Protection**: Token-based request validation
- **Rate Limiting**: Brute force protection and API throttling
- **Input Validation**: Comprehensive server-side validation
- **Password Hashing**: Secure bcrypt password storage
- **Session Security**: Secure session handling with regeneration

**Email & Communications:**
- **SMTP Integration**: Multi-provider email support (Gmail, Outlook, shared hosting)
- **Template System**: Customizable email templates with layout inheritance
- **Queue Support**: Asynchronous email processing capabilities
- **Delivery Tracking**: Email sending status and error handling

---

##  Project Structure

```sh
└── GSCMS.git/
    ├── CLAUDE.md
    ├── README.md
    ├── app
    │   ├── Controllers
    │   │   ├── Admin
    │   │   │   ├── AnnouncementController.php
    │   │   │   ├── CompetitionSetupController.php
    │   │   │   ├── DashboardController.php
    │   │   │   ├── JudgingManagementController.php
    │   │   │   ├── ParticipantManagementController.php
    │   │   │   ├── ReportController.php
    │   │   │   ├── ResourceManagementController.php
    │   │   │   ├── SchoolManagementController.php
    │   │   │   ├── TeamManagementController.php
    │   │   │   └── VenueLogisticsController.php
    │   │   ├── AuthController.php
    │   │   ├── BaseController.php
    │   │   ├── HomeController.php
    │   │   ├── JudgeController.php
    │   │   ├── PublicController.php
    │   │   ├── SchoolCoordinatorController.php
    │   │   ├── TeamCoachController.php
    │   │   └── TestController.php
    │   ├── Core
    │   │   ├── Auth.php
    │   │   ├── CSRF.php
    │   │   ├── Database.php
    │   │   ├── ErrorHandler.php
    │   │   ├── Factory.php
    │   │   ├── Logger.php
    │   │   ├── Mail.php
    │   │   ├── Migration.php
    │   │   ├── RateLimit.php
    │   │   ├── Request.php
    │   │   ├── Response.php
    │   │   ├── Router.php
    │   │   ├── Seeder.php
    │   │   ├── Session.php
    │   │   ├── Validator.php
    │   │   └── helpers.php
    │   ├── Models
    │   │   ├── Announcement.php
    │   │   ├── BaseModel.php
    │   │   ├── Category.php
    │   │   ├── Competition.php
    │   │   ├── ConsentForm.php
    │   │   ├── InventoryItem.php
    │   │   ├── Participant.php
    │   │   ├── Phase.php
    │   │   ├── Resource.php
    │   │   ├── Rubric.php
    │   │   ├── Schedule.php
    │   │   ├── School.php
    │   │   ├── Score.php
    │   │   ├── Team.php
    │   │   ├── User.php
    │   │   └── Venue.php
    │   ├── Views
    │   │   ├── _admin_sidebar.php
    │   │   ├── _footer.php
    │   │   ├── _form_errors.php
    │   │   ├── _header.php
    │   │   ├── admin
    │   │   │   ├── dashboard.php
    │   │   │   └── schools
    │   │   │       ├── form.php
    │   │   │       └── index.php
    │   │   ├── auth
    │   │   │   ├── change_password.php
    │   │   │   ├── forgot_password.php
    │   │   │   ├── login.php
    │   │   │   ├── password_reset.php
    │   │   │   ├── register.php
    │   │   │   ├── register_school.php
    │   │   │   └── reset_password.php
    │   │   ├── dashboard
    │   │   │   └── index.php
    │   │   ├── emails
    │   │   │   ├── email_verification.php
    │   │   │   ├── layout.php
    │   │   │   ├── password_reset.php
    │   │   │   └── welcome.php
    │   │   ├── judge
    │   │   │   ├── dashboard.php
    │   │   │   └── score_entry_form.php
    │   │   ├── layouts
    │   │   │   ├── admin.php
    │   │   │   ├── app.php
    │   │   │   └── public.php
    │   │   ├── public
    │   │   │   ├── announcements.php
    │   │   │   ├── categories.php
    │   │   │   ├── competition_info.php
    │   │   │   ├── home.php
    │   │   │   ├── leaderboard.php
    │   │   │   └── schedule.php
    │   │   └── school_coordinator
    │   │       ├── dashboard.php
    │   │       ├── manage_team.php
    │   │       └── view_resources.php
    │   └── bootstrap.php
    ├── composer.json
    ├── composer.lock
    ├── config
    │   ├── database.php.example
    │   └── routes.php
    ├── database
    │   ├── console
    │   │   ├── drop_views.php
    │   │   ├── export_schema.php
    │   │   ├── migrate.php
    │   │   ├── reset.php
    │   │   ├── seed.php
    │   │   ├── setup.php
    │   │   └── status.php
    │   ├── factories
    │   │   ├── SchoolFactory.php
    │   │   ├── TeamFactory.php
    │   │   └── UserFactory.php
    │   ├── migrations
    │   │   ├── 001_create_migrations_table.php
    │   │   ├── 002_create_users_table.php
    │   │   ├── 003_create_schools_table.php
    │   │   ├── 004_create_phases_table.php
    │   │   ├── 005_create_categories_table.php
    │   │   ├── 006_create_competitions_table.php
    │   │   ├── 007_create_teams_table.php
    │   │   ├── 008_create_participants_table.php
    │   │   ├── 009_create_supporting_tables.php
    │   │   ├── 011_add_foreign_keys.php
    │   │   └── 012_alter_users_status_enum.php
    │   └── seeds
    │       ├── development
    │       │   ├── DevelopmentSeeder.php
    │       │   ├── SampleSchoolsSeeder.php
    │       │   ├── SampleTeamsSeeder.php
    │       │   └── TestUsersSeeder.php
    │       └── production
    │           ├── AdminUserSeeder.php
    │           ├── CategoriesSeeder.php
    │           ├── PhasesSeeder.php
    │           └── ProductionSeeder.php
    ├── public
    │   ├── .htaccess
    │   ├── css
    │   │   ├── admin_style.css
    │   │   ├── home_style.css
    │   │   └── style.css
    │   ├── index.php
    │   ├── js
    │   │   ├── admin_main.js
    │   │   ├── home_script.js
    │   │   └── main.js
    │   └── uploads
    │       └── .gitkeep
    ├── resources
    │   └── views
    │       └── errors
    │           └── 404.php
    └── routes
        └── web.php
```


###  Project Index
<details open>
	<summary><b><code>GSCMS.GIT/</code></b></summary>
	<details> <!-- __root__ Submodule -->
		<summary><b>__root__</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/composer.json'>composer.json</a></b></td>
				<td><code>❯ REPLACE-ME</code></td>
			</tr>
			</table>
		</blockquote>
	</details>
	<details> <!-- config Submodule -->
		<summary><b>config</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/config/database.php.example'>database.php.example</a></b></td>
				<td><code>❯ REPLACE-ME</code></td>
			</tr>
			<tr>
				<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/config/routes.php'>routes.php</a></b></td>
				<td><code>❯ REPLACE-ME</code></td>
			</tr>
			</table>
		</blockquote>
	</details>
	<details> <!-- resources Submodule -->
		<summary><b>resources</b></summary>
		<blockquote>
			<details>
				<summary><b>views</b></summary>
				<blockquote>
					<details>
						<summary><b>errors</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/resources/views/errors/404.php'>404.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
				</blockquote>
			</details>
		</blockquote>
	</details>
	<details> <!-- routes Submodule -->
		<summary><b>routes</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/routes/web.php'>web.php</a></b></td>
				<td><code>❯ REPLACE-ME</code></td>
			</tr>
			</table>
		</blockquote>
	</details>
	<details> <!-- public Submodule -->
		<summary><b>public</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/.htaccess'>.htaccess</a></b></td>
				<td><code>❯ REPLACE-ME</code></td>
			</tr>
			<tr>
				<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/index.php'>index.php</a></b></td>
				<td><code>❯ REPLACE-ME</code></td>
			</tr>
			</table>
			<details>
				<summary><b>css</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/css/style.css'>style.css</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/css/admin_style.css'>admin_style.css</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/css/home_style.css'>home_style.css</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>js</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/js/admin_main.js'>admin_main.js</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/js/main.js'>main.js</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/public/js/home_script.js'>home_script.js</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
				</blockquote>
			</details>
		</blockquote>
	</details>
	<details> <!-- app Submodule -->
		<summary><b>app</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/bootstrap.php'>bootstrap.php</a></b></td>
				<td><code>❯ REPLACE-ME</code></td>
			</tr>
			</table>
			<details>
				<summary><b>Controllers</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/PublicController.php'>PublicController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/SchoolCoordinatorController.php'>SchoolCoordinatorController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/JudgeController.php'>JudgeController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/TeamCoachController.php'>TeamCoachController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/TestController.php'>TestController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/AuthController.php'>AuthController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/HomeController.php'>HomeController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/BaseController.php'>BaseController.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
					<details>
						<summary><b>Admin</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/SchoolManagementController.php'>SchoolManagementController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/CompetitionSetupController.php'>CompetitionSetupController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/TeamManagementController.php'>TeamManagementController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/ResourceManagementController.php'>ResourceManagementController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/JudgingManagementController.php'>JudgingManagementController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/VenueLogisticsController.php'>VenueLogisticsController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/ReportController.php'>ReportController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/AnnouncementController.php'>AnnouncementController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/ParticipantManagementController.php'>ParticipantManagementController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Controllers/Admin/DashboardController.php'>DashboardController.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
				</blockquote>
			</details>
			<details>
				<summary><b>Models</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/ConsentForm.php'>ConsentForm.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Resource.php'>Resource.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Team.php'>Team.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Competition.php'>Competition.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Venue.php'>Venue.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/School.php'>School.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/User.php'>User.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/InventoryItem.php'>InventoryItem.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Rubric.php'>Rubric.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Category.php'>Category.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Phase.php'>Phase.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Score.php'>Score.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Schedule.php'>Schedule.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/BaseModel.php'>BaseModel.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Announcement.php'>Announcement.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Models/Participant.php'>Participant.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>Core</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Database.php'>Database.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/helpers.php'>helpers.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Migration.php'>Migration.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Request.php'>Request.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Factory.php'>Factory.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/CSRF.php'>CSRF.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Validator.php'>Validator.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Auth.php'>Auth.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/ErrorHandler.php'>ErrorHandler.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Mail.php'>Mail.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Response.php'>Response.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Logger.php'>Logger.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/RateLimit.php'>RateLimit.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Router.php'>Router.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Session.php'>Session.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Core/Seeder.php'>Seeder.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>Views</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/_header.php'>_header.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/_admin_sidebar.php'>_admin_sidebar.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/_form_errors.php'>_form_errors.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/_footer.php'>_footer.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
					<details>
						<summary><b>layouts</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/layouts/public.php'>public.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/layouts/admin.php'>admin.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/layouts/app.php'>app.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>school_coordinator</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/school_coordinator/dashboard.php'>dashboard.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/school_coordinator/manage_team.php'>manage_team.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/school_coordinator/view_resources.php'>view_resources.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>auth</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/auth/password_reset.php'>password_reset.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/auth/reset_password.php'>reset_password.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/auth/register.php'>register.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/auth/change_password.php'>change_password.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/auth/register_school.php'>register_school.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/auth/forgot_password.php'>forgot_password.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/auth/login.php'>login.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>emails</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/emails/layout.php'>layout.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/emails/welcome.php'>welcome.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/emails/password_reset.php'>password_reset.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/emails/email_verification.php'>email_verification.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>dashboard</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/dashboard/index.php'>index.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>public</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/public/announcements.php'>announcements.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/public/schedule.php'>schedule.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/public/categories.php'>categories.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/public/home.php'>home.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/public/leaderboard.php'>leaderboard.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/public/competition_info.php'>competition_info.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>judge</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/judge/dashboard.php'>dashboard.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/judge/score_entry_form.php'>score_entry_form.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>admin</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/admin/dashboard.php'>dashboard.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
							<details>
								<summary><b>schools</b></summary>
								<blockquote>
									<table>
									<tr>
										<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/admin/schools/index.php'>index.php</a></b></td>
										<td><code>❯ REPLACE-ME</code></td>
									</tr>
									<tr>
										<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/app/Views/admin/schools/form.php'>form.php</a></b></td>
										<td><code>❯ REPLACE-ME</code></td>
									</tr>
									</table>
								</blockquote>
							</details>
						</blockquote>
					</details>
				</blockquote>
			</details>
		</blockquote>
	</details>
	<details> <!-- database Submodule -->
		<summary><b>database</b></summary>
		<blockquote>
			<details>
				<summary><b>console</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/console/seed.php'>seed.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/console/reset.php'>reset.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/console/setup.php'>setup.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/console/export_schema.php'>export_schema.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/console/drop_views.php'>drop_views.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/console/status.php'>status.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/console/migrate.php'>migrate.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>factories</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/factories/UserFactory.php'>UserFactory.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/factories/SchoolFactory.php'>SchoolFactory.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/factories/TeamFactory.php'>TeamFactory.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>migrations</b></summary>
				<blockquote>
					<table>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/003_create_schools_table.php'>003_create_schools_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/002_create_users_table.php'>002_create_users_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/004_create_phases_table.php'>004_create_phases_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/008_create_participants_table.php'>008_create_participants_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/005_create_categories_table.php'>005_create_categories_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/012_alter_users_status_enum.php'>012_alter_users_status_enum.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/001_create_migrations_table.php'>001_create_migrations_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/007_create_teams_table.php'>007_create_teams_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/009_create_supporting_tables.php'>009_create_supporting_tables.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/006_create_competitions_table.php'>006_create_competitions_table.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					<tr>
						<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/migrations/011_add_foreign_keys.php'>011_add_foreign_keys.php</a></b></td>
						<td><code>❯ REPLACE-ME</code></td>
					</tr>
					</table>
				</blockquote>
			</details>
			<details>
				<summary><b>seeds</b></summary>
				<blockquote>
					<details>
						<summary><b>production</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/production/CategoriesSeeder.php'>CategoriesSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/production/AdminUserSeeder.php'>AdminUserSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/production/PhasesSeeder.php'>PhasesSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/production/ProductionSeeder.php'>ProductionSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
					<details>
						<summary><b>development</b></summary>
						<blockquote>
							<table>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/development/TestUsersSeeder.php'>TestUsersSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/development/SampleTeamsSeeder.php'>SampleTeamsSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/development/SampleSchoolsSeeder.php'>SampleSchoolsSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							<tr>
								<td><b><a href='https://github.com/Vuyani-Magibisela/GSCMS.git/blob/master/database/seeds/development/DevelopmentSeeder.php'>DevelopmentSeeder.php</a></b></td>
								<td><code>❯ REPLACE-ME</code></td>
							</tr>
							</table>
						</blockquote>
					</details>
				</blockquote>
			</details>
		</blockquote>
	</details>
</details>

---
##  Getting Started

###  Prerequisites

Before getting started with GSCMS, ensure your runtime environment meets the following requirements:

- **PHP:** Version 7.4 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.2+
- **Web Server:** Apache 2.4+ or Nginx 1.16+ with mod_rewrite enabled
- **Package Manager:** Composer 2.0+
- **Extensions:** PDO, PDO_MySQL, mbstring, openssl, curl
- **Memory Limit:** Minimum 128MB PHP memory limit
- **File Permissions:** Web server must have write access to `storage/logs/` and `public/uploads/`


###  Installation

**Development Setup:**

1. **Clone the repository:**
```sh
❯ git clone https://github.com/Vuyani-Magibisela/GSCMS.git
❯ cd GSCMS
```

2. **Install dependencies:**
```sh
❯ composer install
```

3. **Configure database:**
```sh
❯ cp config/database.php.example config/database.php
# Edit config/database.php with your database credentials
```

4. **Set up environment variables:**
```sh
# Create .env file and configure:
# - Database settings
# - Email configuration (SMTP)
# - Application settings
```

5. **Initialize database:**
```sh
❯ php database/console/setup.php
```

6. **Start development server:**
```sh
❯ php -S localhost:8000 -t public/
```

**Production Deployment:**

For production deployment on shared hosting:

1. Upload all files to your web server
2. Set file permissions: Files (644), Directories (755), Writable dirs (777)
3. Import database using `local_deployment_prep/database_setup/schema_hosting.sql`
4. Import production data using `seeds_clean.sql`
5. Configure web server document root to `public/` directory
6. Update configuration files with production settings
7. Test all functionality and change default admin password




###  Usage

**Development Server:**
```sh
❯ php -S localhost:8000 -t public/
```

**Database Management:**
```sh
# Run migrations
❯ php database/console/migrate.php

# Seed database with sample data
❯ php database/console/seed.php

# Check migration status
❯ php database/console/migrate.php --status

# Reset database (destructive!)
❯ php database/console/migrate.php --reset
```

**Administration:**
- **Default Admin Login:** `admin@gscms.local` / `password`
- **Admin Panel:** `/admin/dashboard`
- **User Registration:** `/auth/register`
- **School Registration:** `/auth/register-school`

**Key URLs:**
- `/` - Public homepage with competition information
- `/login` - User authentication
- `/admin/*` - Administrative interface
- `/dashboard` - Role-based user dashboard
- `/judge/*` - Judging interface

### Configuration

**Environment Variables (.env):**
```env
# Application Settings
APP_NAME="GDE SciBOTICS CMS"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gde_scibotics_db
DB_USER=your_username
DB_PASS=your_password

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=your.smtp.host
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="GDE SciBOTICS Competition"

# Competition Settings
COMPETITION_YEAR=2025
REGISTRATION_DEADLINE=2025-07-25
COMPETITION_DATE=2025-09-27
```

**File Permissions:**
```bash
# Set correct permissions for production
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 777 storage/logs/
chmod 777 public/uploads/consent_forms/
chmod 777 public/uploads/team_submissions/
```

### Troubleshooting

**Common Issues:**

1. **500 Internal Server Error**
   - Check error logs in `storage/logs/`
   - Verify file permissions (644/755/777)
   - Ensure all dependencies are installed via `composer install`
   - Check .htaccess configuration

2. **Database Connection Issues**
   - Verify credentials in `config/database.php`
   - Ensure database exists and user has proper permissions
   - Check database server is running

3. **Email Not Sending**
   - Verify SMTP credentials in `.env`
   - Check email logs in `storage/logs/emails.log`
   - Test with different SMTP providers
   - Ensure firewall allows SMTP ports

4. **Session/Login Issues**
   - Clear browser cookies
   - Check session directory permissions
   - Verify session configuration in `config/app.php`

###  Testing
Run the test suite using the following command:
**Using `composer`** &nbsp; [<img align="center" src="https://img.shields.io/badge/PHP-777BB4.svg?style={badge_style}&logo=php&logoColor=white" />](https://www.php.net/)

```sh
❯ vendor/bin/phpunit
```

### System Requirements

**Minimum Server Requirements:**
- **CPU**: 1 GHz processor (2+ GHz recommended for production)
- **RAM**: 512MB (2GB+ recommended for production)
- **Storage**: 500MB free space (excludes uploaded files)
- **Bandwidth**: 10Mbps connection for email delivery

**Supported Hosting Platforms:**
- **Shared Hosting**: cPanel-based hosting with PHP support
- **VPS/Dedicated**: Full server control with custom configurations
- **Cloud Hosting**: AWS, Google Cloud, Azure, DigitalOcean
- **Local Development**: XAMPP, WAMP, MAMP, Laravel Valet

**Browser Compatibility:**
- Chrome 70+, Firefox 65+, Safari 12+, Edge 79+
- Mobile browsers: iOS Safari 12+, Chrome Mobile 70+
- JavaScript enabled required for full functionality

### API Documentation

The system includes internal APIs for:
- **User Management**: CRUD operations for users and roles
- **Competition Data**: Teams, participants, and scoring
- **Reporting**: Export functionality and data aggregation
- **Email Services**: Template rendering and delivery status

Future versions will include RESTful API endpoints for third-party integrations.

### Performance & Scalability

**Optimization Features:**
- **Database Indexing**: Optimized queries with proper indexing
- **Connection Pooling**: Efficient database connection management
- **Session Management**: File-based sessions with cleanup routines
- **Asset Optimization**: Minified CSS/JS with cache headers
- **Error Logging**: Structured logging with rotation and cleanup

**Scaling Considerations:**
- **Horizontal Scaling**: Load balancer compatible architecture
- **Database Scaling**: Master-slave replication support
- **File Storage**: External storage integration (S3, CDN)
- **Caching**: Redis/Memcached integration ready
- **Queue Systems**: Background job processing capabilities

**Performance Monitoring:**
- Built-in error logging and debugging tools
- Database query optimization and profiling
- Email delivery tracking and failure handling
- User activity monitoring and analytics

---
##  Project Roadmap

### Completed Features
- [X] **Core MVC Framework**: Custom routing, database layer, and application structure
- [X] **User Authentication**: Multi-role authentication with session management
- [X] **School Management**: Registration, coordinator assignment, and team management
- [X] **Competition Setup**: Multi-phase competitions with categories and schedules
- [X] **Database Migration System**: Automated schema management and seeding
- [X] **Email Integration**: SMTP configuration and automated notifications
- [X] **Admin Interface**: Comprehensive administrative dashboard
- [X] **Judging System**: Score entry, rubrics, and evaluation workflows
- [X] **Production Deployment**: Shared hosting compatibility and deployment tools

### Planned Enhancements
- [ ] **Real-time Notifications**: WebSocket integration for live updates
- [ ] **Mobile Application**: Companion mobile app for participants and judges
- [ ] **Advanced Analytics**: Enhanced reporting with data visualization
- [ ] **API Integration**: RESTful API for third-party integrations
- [ ] **Multi-language Support**: Internationalization for broader accessibility
- [ ] **Document Management**: Enhanced file handling and digital signatures
- [ ] **Social Features**: Team collaboration tools and communication features

---

##  Contributing

- **💬 [Join the Discussions](https://github.com/Vuyani-Magibisela/GSCMS.git/discussions)**: Share your insights, provide feedback, or ask questions.
- **🐛 [Report Issues](https://github.com/Vuyani-Magibisela/GSCMS.git/issues)**: Submit bugs found or log feature requests for the `GSCMS.git` project.
- **💡 [Submit Pull Requests](https://github.com/Vuyani-Magibisela/GSCMS.git/blob/main/CONTRIBUTING.md)**: Review open PRs, and submit your own PRs.

<details closed>
<summary>Contributing Guidelines</summary>

1. **Fork the Repository**: Start by forking the project repository to your github account.
2. **Clone Locally**: Clone the forked repository to your local machine using a git client.
   ```sh
   git clone https://github.com/Vuyani-Magibisela/GSCMS.git
   ```
3. **Create a New Branch**: Always work on a new branch, giving it a descriptive name.
   ```sh
   git checkout -b new-feature-x
   ```
4. **Make Your Changes**: Develop and test your changes locally.
5. **Commit Your Changes**: Commit with a clear message describing your updates.
   ```sh
   git commit -m 'Implemented new feature x.'
   ```
6. **Push to github**: Push the changes to your forked repository.
   ```sh
   git push origin new-feature-x
   ```
7. **Submit a Pull Request**: Create a PR against the original project repository. Clearly describe the changes and their motivations.
8. **Review**: Once your PR is reviewed and approved, it will be merged into the main branch. Congratulations on your contribution!
</details>

<details closed>
<summary>Contributor Graph</summary>
<br>
<p align="left">
   <a href="https://github.com{/Vuyani-Magibisela/GSCMS.git/}graphs/contributors">
      <img src="https://contrib.rocks/image?repo=Vuyani-Magibisela/GSCMS.git">
   </a>
</p>
</details>

---

##  License

This project is developed for the Gauteng Department of Education SciBOTICS program. All rights reserved.

For licensing inquiries or commercial use, please contact the Gauteng Department of Education.

---

##  Acknowledgments

- **Gauteng Department of Education**: For supporting STEM education and the SciBOTICS program
- **Participating Schools**: For their dedication to science education and student development
- **Educators and Judges**: For their commitment to fostering scientific inquiry and innovation
- **Development Team**: For building a robust platform to support educational excellence

### Technical References
- PHP Documentation and best practices
- Modern web security standards and implementations
- Database design patterns for educational management systems
- Email delivery and communication best practices

---

**Built with ❤️ for South African STEM education**

---
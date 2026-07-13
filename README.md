# AI-Powered Career and Pathway Recommendation System

**Student:** Mulima W Sydney | **Reg No:** BIT/2024/58087
**Institution:** Mount Kenya University, School of Computing and Informatics
**Project Type:** Final Year Project

## About This Project

This system was developed to help Kenyan secondary school students choose the right CBC (Competency Based Curriculum) career pathway based on their academic performance and personal interests. Many students struggle to make this decision without proper guidance, and this system uses artificial intelligence to provide personalised recommendations.

The system recommends one of three pathways:
- **STEM**: Science, Technology, Engineering and Mathematics
- **Social Sciences**: Humanities, Business and Social Studies
- **Arts and Sports Science**: Creative Arts, Performing Arts and Sports

## How It Works

A student logs in, enters their scores in five subjects (Mathematics, English, Science, Humanities, and Creative Arts) and selects their main area of interest. The system then uses a hybrid AI model to analyse the data and recommend the most suitable pathway, along with a confidence score and a plain-English explanation of why that pathway was recommended.

The AI combines two techniques. A Decision Tree, weighted at 70%, analyses the student's own scores and interest. A Collaborative KNN, weighted at 30%, looks at what similar students were recommended.

## Technologies Used

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP (no framework) |
| Database | MySQL via XAMPP |
| AI / ML Engine | Python, Scikit-learn, Flask |
| Testing | pytest |

## Project Structure

```
career_system/
├── database/          # MySQL schema and migration scripts
├── ml/                # AI model training scripts and Flask prediction API
├── public/            # Web pages (login, register, dashboards, reports)
├── src/
│   ├── config/        # Database connection, session, CSRF, ML client
│   └── controllers/   # Business logic for auth, student, admin modules
└── tests/             # Automated test suite (38 tests)
```

## Features

**For Students:**
- Register and log in securely
- Enter academic scores and select an area of interest
- Receive an AI-generated pathway recommendation with explanation
- View full recommendation history
- Print or save their report as a PDF

**For Administrators:**
- View all registered students and their recommendations
- Generate printable reports per student and per pathway
- Reset a student's password
- Update the AI model with the latest student data

## Model Performance

| Model | Accuracy | Precision | Recall | F1 Score |
|---|---|---|---|---|
| Decision Tree (used in production) | 81.59% | 81.95% | 81.23% | 81.48% |
| Random Forest (benchmark) | 87.56% | 87.59% | 87.36% | 87.42% |
| KNN (benchmark) | 66.99% | 67.39% | 66.91% | 67.10% |

These figures are from the most recent retrain, which blends the synthetic baseline dataset with real accumulated student submissions. Numbers shift slightly each time the model is retrained as more student data comes in.

The Decision Tree was chosen as the main model because it is interpretable. It can explain why it made a recommendation, which matters for students and parents to trust the result. Random Forest scores higher but is a black box of 200 trees, so it is kept only as a benchmark for comparison.

## How to Run the System

**Requirements:** XAMPP, Python 3.x

1. Start **Apache** and **MySQL** in the XAMPP Control Panel
2. Import the database:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
   ```
3. Install Python dependencies:
   ```
   pip install scikit-learn pandas numpy joblib flask pymysql pytest requests
   ```
4. Generate training data and train the AI model:
   ```
   python ml\generate_data.py
   python ml\train_model.py
   ```
5. Start the AI engine (keep this window open):
   ```
   python ml\api.py
   ```
6. Open the system in your browser:
   ```
   http://localhost/career_system/public/
   ```

## Running the Tests

With XAMPP and the AI engine running:
```
pytest tests\ -v
```

The test suite covers registration, login, lockout, input validation, role-based access, and the full student recommendation flow.

## Security Features

- Passwords are hashed using bcrypt
- All forms are protected against CSRF attacks
- All database queries use parameterised statements (no SQL injection)
- Role-based access control prevents students from accessing admin pages
- Failed login attempts trigger a 5-minute account lockout after 5 tries

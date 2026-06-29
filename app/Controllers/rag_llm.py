from fastapi import FastAPI
from pydantic import BaseModel
import uvicorn

from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_google_genai import GoogleGenerativeAIEmbeddings, ChatGoogleGenerativeAI
from langchain_community.vectorstores import FAISS
from langchain_core.prompts import PromptTemplate
from langchain_core.runnables import RunnablePassthrough
from dotenv import load_dotenv
import os
from datetime import datetime
# from zoneinfo import ZoneInfo
from fastapi.middleware.cors import CORSMiddleware

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.abspath(os.path.join(BASE_DIR, "..", ".."))
RAG_HOST = "127.0.0.1"
RAG_PORT = 1884

# LOAD API KEY
load_dotenv(os.path.join(PROJECT_DIR, ".env"))
api_key = os.getenv("GOOGLE_API_KEY")

# LOAD DOCUMENT SOURCE
with open(os.path.join(PROJECT_DIR, "storage", "rag", "RAG.txt"), "r", encoding="utf-8") as f:
    transcript = f.read()

# SPLIT TEXT
text_splitter = RecursiveCharacterTextSplitter(
    chunk_size=500,
    chunk_overlap=50
)
docs = text_splitter.create_documents([transcript])

# EMBEDDING
embedding = GoogleGenerativeAIEmbeddings(
    model="gemini-embedding-001",
    google_api_key=api_key
)
vector_db = FAISS.from_documents(docs, embedding)
retriever = vector_db.as_retriever(search_kwargs={"k": 3})

# LLM
llm = ChatGoogleGenerativeAI(
    model="gemini-3.1-flash-lite-preview",
    temperature=0.2,
    google_api_key=api_key
)

# PROMPT
prompt = PromptTemplate(
    template="""Bạn là trợ lý AI của Plantify Co, công ty chuyên thiết kế và cung cấp giải pháp cây xanh cho không gian doanh nghiệp.

Nhiệm vụ của bạn là trả lời câu hỏi của khách hàng về:
- Dịch vụ cây cảnh và decor xanh
- Các loại cây phổ biến
- Quy trình tư vấn và thi công
- Chăm sóc định kỳ
- Thông tin liên hệ và giờ làm việc
- Lợi ích của cây xanh trong không gian

Hướng dẫn trả lời:
- Trả lời bằng tiếng Việt một cách thân thiện, chuyên nghiệp
- Sử dụng thông tin từ dữ liệu được cung cấp
- Nếu khách hỏi về thời gian, lịch hẹn hoặc giờ hiện tại, hãy dùng thời gian Việt Nam bên dưới, không dùng UTC hoặc múi giờ máy chủ
- Nếu không có thông tin, hãy gợi ý liên hệ trực tiếp
- Giữ câu trả lời ngắn gọn, hữu ích
- Khuyến khích khách hàng liên hệ để được tư vấn chi tiết

Thời gian hiện tại tại Việt Nam:
{current_time}

Dữ liệu:
{context}

Câu hỏi của khách hàng:
{question}

Trả lời:""",
    input_variables=["context", "question", "current_time"]
)


def format_docs(docs):
    return "\n\n".join(doc.page_content for doc in docs)


def vietnam_time(_question):
    return datetime.now().strftime("%H:%M:%S ngày %d/%m/%Y")

rag_chain = (
    {
        "context": retriever | format_docs,
        "question": RunnablePassthrough(),
        "current_time": vietnam_time,
    }
    | prompt
    | llm
)

app = FastAPI()
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class QueryRequest(BaseModel):
    question: str

@app.on_event("startup")
async def startup_message():
    rag_path = os.path.join(PROJECT_DIR, "storage", "rag", "RAG.txt")
    print("=" * 60)
    print("Plantify RAG chatbot is running")
    print(f"Host: http://{RAG_HOST}:{RAG_PORT}")
    print(f"Chat API: http://{RAG_HOST}:{RAG_PORT}/chat")
    print(f"RAG data: {rag_path}")
    print(f"RAG data exists: {'yes' if os.path.isfile(rag_path) else 'no'}")
    print(f"GOOGLE_API_KEY loaded: {'yes' if api_key else 'no'}")
    print("=" * 60)

@app.post("/chat")
async def chat(request: QueryRequest):
    try:
        response = rag_chain.invoke(request.question)
        return {"response": response.content}
    except Exception as e:
        print("CHATBOT ERROR:", repr(e))
        return {"response": "Xin lỗi, có lỗi xảy ra. Vui lòng thử lại sau."}

if __name__ == "__main__":
    uvicorn.run(app, host=RAG_HOST, port=RAG_PORT)

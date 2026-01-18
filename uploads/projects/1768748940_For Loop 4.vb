Imports System

Module Program
    Sub Main(args As String())
        Console.WriteLine("Enter a number:")
        Dim n As Integer = Integer.Parse(Console.ReadLine())
        Dim f As Integer = 1
        For i As Integer = 1 To n
            f *= i
        Next
        Console.WriteLine("Factorial of " & n & " is :" & f)
    End Sub
End Module
